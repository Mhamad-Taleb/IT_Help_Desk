<x-guest-layout>
    @php($idsLogoPath = 'images/ids-logo-white.png')
    <div class="flex min-h-screen flex-col px-4 py-4 text-white sm:px-6 sm:py-5 lg:h-screen lg:px-10 lg:py-5">
        <header class="flex items-center justify-center gap-6 lg:justify-start">
            <a href="/" class="flex items-center">
                @if (file_exists(public_path($idsLogoPath)))
                    <span class="inline-flex rounded-2xl border border-white/10 bg-white/5 p-2 shadow-[0_14px_30px_rgba(1,25,33,0.22)] backdrop-blur-sm">
                        <img src="{{ asset($idsLogoPath) }}" alt="IDS Logo" class="h-8 w-auto max-w-[15rem] rounded-md object-contain sm:h-10 sm:max-w-[17rem] lg:h-11 lg:max-w-[20rem]" />
                    </span>
                @else
                    <span class="flex items-center gap-4">
                        <x-application-logo class="h-11 w-11 fill-current text-white" />
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="text-3xl font-semibold tracking-[0.2em]">IDS</span>
                                <span class="hidden h-8 w-px bg-white/40 md:block"></span>
                            </div>
                            <p class="text-xs uppercase tracking-[0.35em] text-white/75 sm:text-sm">
                                Integrated Digital Systems
                            </p>
                        </div>
                    </span>
                @endif
            </a>
        </header>

        <div class="flex flex-1 flex-col items-stretch gap-4 pb-3 pt-4 lg:min-h-0 lg:gap-0 lg:pb-0 lg:pt-6 xl:flex-row">
            <section class="order-2 flex flex-1 flex-col justify-between rounded-[2rem] border border-white/10 bg-white/5 p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] backdrop-blur-[2px] sm:p-6 lg:min-h-0 lg:p-8 xl:order-1 xl:mr-6 xl:p-9">
                <div class="mx-auto flex max-w-3xl flex-col items-center text-center lg:mx-0 lg:block lg:text-left">
                    <div class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.32em] text-white/85 sm:text-xs">
                        Internal Support Platform
                    </div>

                    <h1 class="mt-5 max-w-4xl text-[2rem] font-semibold uppercase leading-[0.95] tracking-[0.16em] text-white sm:text-[2.8rem] sm:tracking-[0.2em] md:text-[3.4rem] lg:text-6xl lg:tracking-[0.22em] xl:text-[4.75rem] xl:tracking-[0.24em]">
                        IT Help Desk
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-6 text-cyan-50/88 sm:mt-5 sm:text-base sm:leading-7">
                        A secure internal workspace for IDS employees, support agents, managers, and admins to manage technical requests through one structured ticketing experience.
                    </p>

                    <div class="mt-6 grid w-full max-w-3xl gap-3 sm:grid-cols-2 lg:mt-7 xl:flex xl:flex-wrap">
                        <div class="ids-chip w-full justify-center lg:justify-start">
                            Centralized ticket tracking
                        </div>
                        <div class="ids-chip w-full justify-center lg:justify-start">
                            Role-based internal access
                        </div>
                        <div class="ids-chip w-full justify-center sm:col-span-2 lg:justify-start xl:w-auto">
                            Built for Integrated Digital Systems
                        </div>
                    </div>
                </div>

                <div class="mt-7 grid gap-4 lg:mt-8 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-end lg:gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="mx-auto max-w-xl text-center lg:mx-0 lg:self-end lg:text-left">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-white/60">Operational Focus</p>
                        <p class="mt-2 text-sm leading-6 text-white/74 sm:leading-7">
                            This environment is provisioned internally. Public registration is disabled, and access is granted through administrator-managed usernames.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2 lg:self-end">
                        <div class="ids-stat-card">
                            <span class="ids-stat-label">Access</span>
                            <span class="ids-stat-value">Username</span>
                        </div>
                        <div class="ids-stat-card">
                            <span class="ids-stat-label">Security</span>
                            <span class="ids-stat-value">Managed</span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="order-1 w-full xl:order-2 xl:flex xl:w-[31rem] xl:min-h-0 xl:shrink-0">
                <div class="ids-auth-card mx-auto flex w-full max-w-2xl flex-col rounded-[2rem] border border-white/15 bg-white/95 p-5 text-slate-900 shadow-[0_30px_80px_rgba(2,21,28,0.32)] sm:p-7 lg:p-8 xl:h-full xl:max-w-none xl:p-7">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#0f7b92]">Welcome Back</p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-[0.01em] text-slate-950 sm:text-3xl">
                            Sign in to the IDS help desk
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500 sm:text-[0.97rem]">
                            Use your assigned internal username and password to access your support workspace.
                        </p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}" class="mt-5 flex flex-1 flex-col justify-between">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <x-input-label for="username" :value="__('Username')" class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500" />
                                <x-text-input
                                    id="username"
                                    class="mt-3 block h-14 w-full rounded-2xl border border-slate-200 bg-slate-50/80 px-5 text-base text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-[#0f7b92] focus:bg-white focus:ring-[#0f7b92]"
                                    type="text"
                                    name="username"
                                    :value="old('username')"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Enter your username"
                                />
                                <x-input-error :messages="$errors->get('username')" class="mt-2 text-sm text-rose-600" />
                            </div>

                            <div>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                    <x-input-label for="password" :value="__('Password')" class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500" />
                                    @if (Route::has('password.request'))
                                        <a class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0f7b92] transition hover:text-[#095b6e] focus:outline-none focus:ring-2 focus:ring-[#0f7b92] focus:ring-offset-2 sm:text-right" href="{{ route('password.request') }}">
                                            Forgot Password
                                        </a>
                                    @endif
                                </div>

                                <x-text-input
                                    id="password"
                                    class="mt-3 block h-14 w-full rounded-2xl border border-slate-200 bg-slate-50/80 px-5 text-base text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-[#0f7b92] focus:bg-white focus:ring-[#0f7b92]"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                />
                                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
                            </div>

                            <div class="pt-1">
                                <label for="remember_me" class="inline-flex items-center gap-3 text-sm text-slate-500">
                                    <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#0f7b92] shadow-none focus:ring-[#0f7b92]" name="remember">
                                    <span>Keep me signed in</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <x-primary-button class="ids-login-button w-full justify-center rounded-2xl border-0 px-6 py-4 text-sm font-semibold uppercase tracking-[0.28em] text-white shadow-[0_20px_35px_rgba(15,123,146,0.28)] focus:ring-[#0f7b92] focus:ring-offset-2">
                                {{ __('Log in') }}
                            </x-primary-button>

                            <div class="rounded-[1.45rem] border border-slate-200 bg-slate-50 px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Access Policy</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    This portal is for IDS team members only. Usernames are created and managed by the help desk administrator.
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</x-guest-layout>
