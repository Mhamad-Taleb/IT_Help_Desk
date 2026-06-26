<x-app-layout>
    <x-slot name="header">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-cyan-600 text-2xl font-black text-white shadow-lg shadow-cyan-600/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.28em] text-cyan-600">
                            My Account
                        </p>

                        <h2 class="mt-1 text-3xl font-black text-slate-950">
                            Profile Settings
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Update your account details and security settings.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-50 px-5 py-4 text-sm">
                    <p class="font-bold text-slate-900">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mt-1 text-slate-500">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">


                

                {{-- Content --}}
                <main class="space-y-6">

                    <section id="profile-info" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.24em] text-cyan-600">
                                    Profile Information
                                </p>

                                <h3 class="mt-2 text-2xl font-black text-slate-950">
                                    Personal Details
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Update your name and email address.
                                </p>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </section>

                    <section id="password" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.24em] text-cyan-600">
                                    Security
                                </p>

                                <h3 class="mt-2 text-2xl font-black text-slate-950">
                                    Change Password
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Choose a secure password to protect your account.
                                </p>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </section>

                    <section id="delete-account" class="rounded-[2rem] border border-rose-200 bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-start justify-between gap-4 border-b border-rose-100 pb-5">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.24em] text-rose-600">
                                    Danger Zone
                                </p>

                                <h3 class="mt-2 text-2xl font-black text-slate-950">
                                    Delete Account
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    This action is permanent and cannot be undone.
                                </p>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </section>

                </main>
            

        </div>
    </div>
</x-app-layout>