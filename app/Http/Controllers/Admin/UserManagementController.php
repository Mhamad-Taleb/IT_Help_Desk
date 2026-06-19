<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
