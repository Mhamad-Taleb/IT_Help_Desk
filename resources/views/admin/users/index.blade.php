<x-app-layout>
    <x-slot name="header">
        <div class="rounded-3xl bg-gradient-to-r from-slate-950 to-slate-800 px-6 py-6 text-white shadow-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-300">Admin Workspace</p>
                    <h2 class="mt-2 text-2xl font-bold">User & Role Management</h2>
                    <p class="mt-1 text-sm text-slate-300">
                        Manage users, roles, credentials, and account access from one place.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 px-5 py-3 text-center backdrop-blur">
                    <p class="text-3xl font-bold">{{ $userCount }}</p>
                    <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Accounts</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">

        @if (session('status'))
            <x-auto-dismiss-alert :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-auto-dismiss-alert type="error" :message="$errors->first()" />
        @endif

        {{-- Create User --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Create New User</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Add a new account and assign role access immediately.
                    </p>
                </div>

                <span class="w-fit rounded-full bg-cyan-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.22em] text-cyan-700">
                    New Account
                </span>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="grid gap-5 md:grid-cols-2">
                @csrf

                <div>
                    <x-input-label for="create_name" value="Full Name" />
                    <x-text-input id="create_name" name="name" type="text" class="mt-2 block w-full rounded-2xl" :value="old('name')" required />
                </div>

                <div>
                    <x-input-label for="create_username" value="Username" />
                    <x-text-input id="create_username" name="username" type="text" class="mt-2 block w-full rounded-2xl" :value="old('username')" required />
                </div>

                <div>
                    <x-input-label for="create_email" value="Email Address" />
                    <x-text-input id="create_email" name="email" type="email" class="mt-2 block w-full rounded-2xl" :value="old('email')" required />
                </div>

                <div>
                    <x-input-label for="create_role" value="Role" />
                    <select id="create_role" name="role"
                        class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', \App\Enums\UserRole::Employee->value) === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="create_password" value="Temporary Password" />
                    <x-text-input id="create_password" name="password" type="password" class="mt-2 block w-full rounded-2xl" required />
                </div>

                <div>
                    <x-input-label for="create_password_confirmation" value="Confirm Password" />
                    <x-text-input id="create_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full rounded-2xl" required />
                </div>

                <div class="md:col-span-2 flex flex-col gap-4 rounded-2xl bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-600">
                        The user can login immediately using these credentials.
                    </p>

                    <x-primary-button class="justify-center rounded-2xl bg-slate-900 px-6 py-3">
                        Create User
                    </x-primary-button>
                </div>
            </form>
        </section>

        {{-- Users List --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">All Users</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage account details, roles, and access.
                    </p>
                </div>

                <span class="w-fit rounded-full bg-slate-900 px-4 py-2 text-xs font-bold uppercase tracking-[0.22em] text-white">
                    {{ $userCount }} Accounts
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Username</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Activity</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $managedUser)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-base font-bold text-white shadow-sm">
                                            {{ strtoupper(substr($managedUser->name, 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-bold text-slate-900">{{ $managedUser->name }}</p>

                                                @if ($managedUser->id === auth()->id())
                                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-amber-700">
                                                        You
                                                    </span>
                                                @endif
                                            </div>

                                            <p class="mt-1 text-sm text-slate-500">{{ $managedUser->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700">
                                        {{ $managedUser->username }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full bg-cyan-50 px-3 py-1.5 text-sm font-bold text-cyan-700">
                                        {{ $managedUser->role->label() }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    @if (! $managedUser->isAdmin())
                                        <a
                                            href="{{ route('admin.users.activity', $managedUser) }}"
                                            class="inline-flex items-center rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-bold text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100"
                                        >
                                            View Activity
                                        </a>
                                    @else
                                        <span class="text-sm font-medium text-slate-400">Admin account</span>
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('edit-user-{{ $managedUser->id }}').classList.toggle('hidden')"
                                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            onclick="document.getElementById('delete-modal-{{ $managedUser->id }}').classList.remove('hidden'); document.getElementById('delete-modal-{{ $managedUser->id }}').classList.add('flex')"
                                            class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Edit View --}}
                            <tr id="edit-user-{{ $managedUser->id }}" class="hidden">
                                <td colspan="5" class="bg-slate-50 px-6 py-6">
                                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="flex items-center gap-4">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-50 text-xl font-bold text-cyan-700">
                                                    ✎
                                                </div>

                                                <div>
                                                    <h4 class="text-lg font-bold text-slate-900">
                                                        Edit User
                                                    </h4>
                                                    <p class="text-sm text-slate-500">
                                                        Update {{ $managedUser->name }} account information and role.
                                                    </p>
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                onclick="document.getElementById('edit-user-{{ $managedUser->id }}').classList.add('hidden')"
                                                class="w-fit rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50">
                                                Cancel
                                            </button>
                                        </div>

                                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="grid gap-5 lg:grid-cols-4">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label :for="'name_'.$managedUser->id" value="Name" />
                                                <x-text-input :id="'name_'.$managedUser->id" name="name" type="text"
                                                    class="mt-2 block w-full rounded-2xl bg-slate-50"
                                                    :value="old('name', $managedUser->name)" required />
                                            </div>

                                            <div>
                                                <x-input-label :for="'username_'.$managedUser->id" value="Username" />
                                                <x-text-input :id="'username_'.$managedUser->id" name="username" type="text"
                                                    class="mt-2 block w-full rounded-2xl bg-slate-50"
                                                    :value="old('username', $managedUser->username)" required />
                                            </div>

                                            <div>
                                                <x-input-label :for="'email_'.$managedUser->id" value="Email" />
                                                <x-text-input :id="'email_'.$managedUser->id" name="email" type="email"
                                                    class="mt-2 block w-full rounded-2xl bg-slate-50"
                                                    :value="old('email', $managedUser->email)" required />
                                            </div>

                                            <div>
                                                <x-input-label :for="'role_'.$managedUser->id" value="Role" />
                                                <select id="role_{{ $managedUser->id }}" name="role"
                                                    class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->value }}" @selected($managedUser->role === $role)>
                                                            {{ $role->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="lg:col-span-4 flex justify-end border-t border-slate-100 pt-5">
                                                <x-primary-button class="rounded-2xl bg-slate-900 px-6 py-3 text-xs tracking-[0.22em]">
                                                    Save Changes
                                                </x-primary-button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Delete Modal --}}
                            <div id="delete-modal-{{ $managedUser->id }}"
                                class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 px-4">

                                <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-3xl font-bold text-rose-600">
                                        !
                                    </div>

                                    <div class="mt-5 text-center">
                                        <h3 class="text-xl font-bold text-slate-900">
                                            Delete this user?
                                        </h3>

                                        <p class="mt-2 text-sm leading-6 text-slate-500">
                                            Are you sure you want to delete
                                            <span class="font-bold text-slate-900">{{ $managedUser->name }}</span>?
                                            This action cannot be undone.
                                        </p>
                                    </div>

                                    <div class="mt-6 flex justify-center gap-3">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('delete-modal-{{ $managedUser->id }}').classList.add('hidden'); document.getElementById('delete-modal-{{ $managedUser->id }}').classList.remove('flex')"
                                            class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                            Cancel
                                        </button>

                                        <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-bold text-white hover:bg-rose-700">
                                                Yes, Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <p class="font-bold text-slate-700">No users found.</p>
                                    <p class="mt-1 text-sm text-slate-500">Create your first user from the form above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
