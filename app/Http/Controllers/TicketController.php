<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tickets = Ticket::query()
            ->with(['category', 'creator', 'assignee'])
            ->visibleTo($user)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categoryOptions = Category::query()->active()->ordered()->get();

        $scopeQuery = Ticket::query()->visibleTo($user);

        return view('tickets.index', [
            'categories' => $categoryOptions,
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'tickets' => $tickets,
            'totalTickets' => (clone $scopeQuery)->count(),
            'openTickets' => (clone $scopeQuery)->where('status', TicketStatus::Open->value)->count(),
            'resolvedTickets' => (clone $scopeQuery)->where('status', TicketStatus::Resolved->value)->count(),
            'assignedTickets' => (clone $scopeQuery)->whereNotNull('assigned_to')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tickets.create', [
            'assignees' => $this->assignableUsers(),
            'categories' => Category::query()->active()->ordered()->get(),
            'priorities' => TicketPriority::cases(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate($this->rulesFor($user));

        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'],
            'status' => TicketStatus::Open,
            'created_by' => $user->id,
            'assigned_to' => $this->canManageWorkflow($user) ? ($validated['assigned_to'] ?? null) : null,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket created successfully.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);

        $ticket->load(['category', 'creator', 'assignee', 'messages.user']);
        $visibleMessages = $this->visibleMessagesFor($ticket, $user);

        return view('tickets.show', [
            'assignees' => $this->assignableUsers(),
            'canDelete' => $this->canDelete($user, $ticket),
            'canManageWorkflow' => $this->canManageWorkflow($user),
            'canUpdate' => $this->canUpdate($user, $ticket),
            'categories' => Category::query()->active()->ordered()->get(),
            'messages' => $visibleMessages,
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'ticket' => $ticket,
            'timelineEvents' => $this->buildTimelineEvents($ticket, $visibleMessages),
            'user' => $user,
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanUpdate($user, $ticket);

        $validated = $request->validate($this->rulesFor($user, $ticket));

        $ticket->fill([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'],
        ]);

        if ($this->canManageWorkflow($user)) {
            $ticket->status = $validated['status'];
            $ticket->assigned_to = $validated['assigned_to'] ?? null;

            $status = TicketStatus::from($validated['status']);
            $ticket->resolved_at = in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)
                ? ($ticket->resolved_at ?? now())
                : null;
            $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        }

        $ticket->save();

        if ($ticket->status->isFinal()) {
            $ticket->messages()->delete();
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated successfully.');
    }

    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->ensureCanDelete($request->user(), $ticket);

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket deleted successfully.');
    }

    public function storeMessage(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => false,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Comment added successfully.');
    }

    protected function canManageWorkflow(User $user): bool
    {
        return ! $user->hasRole(UserRole::Employee);
    }

    protected function canUpdate(User $user, Ticket $ticket): bool
    {
        if ($this->canManageWorkflow($user)) {
            return true;
        }

        return $ticket->created_by === $user->id && ! $ticket->status->isFinal();
    }

    protected function canDelete(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::Admin, UserRole::Manager])) {
            return true;
        }

        return $user->hasRole(UserRole::Employee)
            && $ticket->created_by === $user->id
            && $ticket->status === TicketStatus::Open;
    }

    protected function ensureCanView(User $user, Ticket $ticket): void
    {
        abort_unless(
            $this->canManageWorkflow($user) || $ticket->created_by === $user->id,
            403,
        );
    }

    protected function ensureCanUpdate(User $user, Ticket $ticket): void
    {
        abort_unless($this->canUpdate($user, $ticket), 403);
    }

    protected function ensureCanDelete(User $user, Ticket $ticket): void
    {
        abort_unless($this->canDelete($user, $ticket), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesFor(User $user, ?Ticket $ticket = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];

        if ($this->canManageWorkflow($user)) {
            $rules['status'] = ['required', Rule::enum(TicketStatus::class)];
            $rules['assigned_to'] = [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->whereIn('role', [
                        UserRole::Admin->value,
                        UserRole::Manager->value,
                        UserRole::SupportAgent->value,
                    ]);
                }),
            ];
        }

        return $rules;
    }

    protected function assignableUsers()
    {
        return User::query()
            ->supportAssignable()
            ->orderBy('name')
            ->get();
    }

    protected function visibleMessagesFor(Ticket $ticket, User $user)
    {
        return $ticket->messages->values();
    }

    protected function buildTimelineEvents(Ticket $ticket, $messages)
    {
        $events = collect([
            [
                'title' => 'Ticket created',
                'description' => 'Request opened by '.$ticket->creator->name.'.',
                'occurred_at' => $ticket->created_at,
                'tone' => 'bg-cyan-400',
                'type' => 'system',
            ],
        ]);

        foreach ($messages->sortBy('created_at') as $message) {
            $events->push([
                'title' => 'Comment added',
                'description' => $message->user->name.' added a ticket comment.',
                'occurred_at' => $message->created_at,
                'tone' => 'bg-blue-400',
                'type' => 'comment',
            ]);
        }

        if ($ticket->resolved_at) {
            $events->push([
                'title' => 'Ticket resolved',
                'description' => 'The issue was marked as resolved.',
                'occurred_at' => $ticket->resolved_at,
                'tone' => 'bg-emerald-400',
                'type' => 'system',
            ]);
        }

        if ($ticket->closed_at) {
            $events->push([
                'title' => 'Ticket closed',
                'description' => 'The workflow was completed and closed.',
                'occurred_at' => $ticket->closed_at,
                'tone' => 'bg-slate-300',
                'type' => 'system',
            ]);
        }

        return $events
            ->sortBy('occurred_at')
            ->values();
    }
}
