@php
    $idsLogoPath = 'images/ids-logo-white.png';
    $navigationItems = [
        [
            'label' => 'Dashboard',
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => 'Tickets',
            'route' => route('tickets.index'),
            'active' => request()->routeIs('tickets.*'),
        ],
        [
            'label' => 'Profile',
            'route' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
        ],
        [
            'label' => 'History',
            'route' => route('history'),
            'active' => request()->routeIs('history'),
        ],
    ];

    if (Auth::user()->isAdmin()) {
        $navigationItems[] = [
            'label' => 'User Management',
            'route' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
        ];

        $navigationItems[] = [
            'label' => 'Categories',
            'route' => route('admin.categories.index'),
            'active' => request()->routeIs('admin.categories.*'),
        ];
    }
@endphp

<div x-data="{ open: false }" class="flex h-screen overflow-hidden">
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden"
        style="display: none;"
    ></div>

    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-[18rem] flex-col border-r border-white/10 bg-[#0c5c70] text-white shadow-[0_30px_80px_rgba(2,21,28,0.28)] transition-transform duration-300 ease-out lg:static lg:z-auto lg:w-[18.5rem] lg:translate-x-0 lg:shadow-none xl:w-[20rem]"
    >
        <div class="flex h-full flex-col px-4 py-5 sm:px-5">
            <div class="app-sidebar-card rounded-[1.65rem] border border-white/12 bg-white/6 p-3.5 backdrop-blur-sm">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center">
                    @if (file_exists(public_path($idsLogoPath)))
                        <img src="{{ asset($idsLogoPath) }}" alt="IDS Logo" class="h-9 w-auto max-w-full object-contain" />
                    @else
                        <span class="flex items-center gap-3">
                            <x-application-logo class="h-10 w-10 fill-current text-white" />
                            <span class="text-lg font-semibold tracking-[0.18em]">IDS</span>
                        </span>
                    @endif
                </a>

                <div class="app-user-card mt-4 rounded-[1.25rem] border border-white/10 bg-slate-950/12 px-3.5 py-3.5">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.32em] text-cyan-100/70">Signed In As</p>
                    <h2 class="mt-2 text-[1.35rem] font-semibold leading-tight text-white">{{ Auth::user()->name }}</h2>
                </div>
            </div>

            <nav class="mt-5 flex-1 space-y-2 overflow-y-auto pr-1">
                @foreach ($navigationItems as $item)
                    <a
                        href="{{ $item['route'] }}"
                        @class([
                            'app-nav-link flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition duration-200',
                            'app-nav-link-active bg-white text-slate-900 shadow-[0_18px_30px_rgba(255,255,255,0.12)]' => $item['active'],
                            'app-nav-link-inactive text-cyan-50/88 hover:bg-white/10 hover:text-white' => ! $item['active'],
                        ])
                    >
                        <span class="app-nav-dot h-2.5 w-2.5 rounded-full {{ $item['active'] ? 'bg-[#0f7b92]' : 'bg-cyan-200/40' }}"></span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="app-logout-panel mt-5 rounded-[1.6rem] border border-white/10 bg-white/6 p-3 backdrop-blur-sm">
                <a
                    href="{{ route('profile.edit') }}"
                    class="mb-3 flex items-center justify-between rounded-2xl px-3 py-3 text-sm text-cyan-50/88 transition hover:bg-white/10 hover:text-white lg:hidden"
                >
                    <span>Account Settings</span>
                    <span class="text-xs uppercase tracking-[0.24em]">Open</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="app-logout-button flex w-full items-center justify-between rounded-2xl bg-slate-950/30 px-4 py-3 text-sm font-semibold uppercase tracking-[0.22em] text-white transition hover:bg-slate-950/45"
                    >
                        <span>Log Out</span>
                        <span class="text-lg leading-none">&gt;</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="app-main-shell flex min-w-0 flex-1 flex-col overflow-hidden">
        <div class="app-mobile-header border-b border-slate-200/80 bg-white/85 px-4 py-4 backdrop-blur-md sm:px-6 lg:hidden">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-slate-900">
                    <span class="rounded-2xl bg-[#0c5c70] px-3 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-white">
                        IDS
                    </span>
                    <span class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-700">Help Desk</span>
                </a>

                <button
                    @click="open = !open"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-3 text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>
        </div>

        <main class="app-main-view flex-1 overflow-y-auto">
            @isset($header)
                <header class="app-main-header relative z-40 border-b border-slate-200/80 bg-white/70 px-4 py-5 backdrop-blur-md sm:px-6 lg:px-8 xl:px-10">
                    {{ $header }}
                </header>
            @endisset

            <div class="app-main-content relative z-0 px-4 py-6 sm:px-6 lg:px-8 xl:px-10">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
