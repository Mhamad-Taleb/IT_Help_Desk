<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display the user management screen.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'roles' => UserRole::cases(),
            'userCount' => User::query()->count(),
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function activity(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $relatedTickets = $this->relatedTicketsQuery($user)
            ->with(['category:id,name', 'creator:id,name', 'assignee:id,name'])
            ->latest()
            ->paginate(8, ['*'], 'tickets_page');

        $relatedTicketIds = $this->relatedTicketsQuery($user)->select('tickets.id');
        $activityLogs = AuditLog::query()
            ->with(['actor:id,name', 'targetUser:id,name', 'ticket:id,ticket_number'])
            ->where(function ($query) use ($user, $relatedTicketIds): void {
                $query
                    ->where('user_id', $user->id)
                    ->orWhere('target_user_id', $user->id)
                    ->orWhereIn('ticket_id', $relatedTicketIds);
            })
            ->latest()
            ->paginate(12, ['*'], 'logs_page');
        $activeStatuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::Pending->value,
        ];
        $ticketSummary = $this->relatedTicketsCollection($user);

        return view('admin.users.activity', [
            'managedUser' => $user,
            'linkedTickets' => $relatedTickets,
            'activityLogs' => $activityLogs,
            'ticketCount' => $ticketSummary->count(),
            'activeTicketCount' => $ticketSummary->filter(fn (Ticket $ticket): bool => in_array($ticket->status->value, $activeStatuses, true))->count(),
            'resolvedTicketCount' => $ticketSummary->filter(fn (Ticket $ticket): bool => in_array($ticket->status->value, [TicketStatus::Resolved->value, TicketStatus::Closed->value], true))->count(),
            'activityCount' => AuditLog::query()
                ->where(function ($query) use ($user, $relatedTicketIds): void {
                    $query
                        ->where('user_id', $user->id)
                        ->orWhere('target_user_id', $user->id)
                        ->orWhereIn('ticket_id', $relatedTicketIds);
                })
                ->count(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->storeRules());

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => UserRole::from($validated['role']),
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        AuditLogger::record(
            'user.created',
            auth()->user()->name." created user {$user->name}.",
            actor: auth()->user(),
            subject: $user,
            targetUser: $user,
        );

        return back()->with('status', "{$user->name} was created successfully.");
    }

    /**
     * Update the selected user's details and role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->updateRules($user));

        $newRole = UserRole::from($validated['role']);

        if (
            $user->hasRole(UserRole::Admin)
            && $newRole !== UserRole::Admin
            && User::query()->byRole(UserRole::Admin)->count() <= 1
        ) {
            return back()->withErrors([
                'role' => 'At least one admin account must remain in the system.',
            ]);
        }

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $newRole,
        ]);

        AuditLogger::record(
            'user.updated',
            auth()->user()->name." updated user {$user->name}.",
            actor: auth()->user(),
            subject: $user,
            targetUser: $user,
        );

        return back()->with('status', "{$user->name} was updated successfully.");
    }

    /**
     * Delete the selected user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors([
                'user' => 'You cannot delete the account currently signed in from this screen.',
            ]);
        }

        if (
            $user->hasRole(UserRole::Admin)
            && User::query()->byRole(UserRole::Admin)->count() <= 1
        ) {
            return back()->withErrors([
                'user' => 'At least one admin account must remain in the system.',
            ]);
        }

        $deletedName = $user->name;
        $deletedUserId = $user->id;

        $user->delete();

        AuditLogger::record(
            'user.deleted',
            auth()->user()->name." deleted user {$deletedName}.",
            actor: auth()->user(),
            targetUser: null,
            properties: ['deleted_user_id' => $deletedUserId, 'deleted_user_name' => $deletedName],
        );

        return back()->with('status', "{$deletedName} was deleted successfully.");
    }

    /**
     * Validation rules for storing a user.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'role' => ['required', 'string', Rule::in(UserRole::values())],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Validation rules for updating a user.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function updateRules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(UserRole::values())],
        ];
    }

    protected function relatedTicketsQuery(User $user)
    {
        return match ($user->role) {
            UserRole::Employee => Ticket::query()->where('created_by', $user->id),
            UserRole::SupportAgent => Ticket::query()->where('assigned_to', $user->id),
            UserRole::Manager => Ticket::query()->where(function ($query) use ($user): void {
                $query
                    ->where('created_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            }),
            UserRole::Admin => Ticket::query()->whereRaw('1 = 0'),
        };
    }

    protected function relatedTicketsCollection(User $user): Collection
    {
        return $this->relatedTicketsQuery($user)->get();
    }
}
