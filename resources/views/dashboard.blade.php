<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-[#0f7b92]">Command Center</p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight text-slate-950">
                    IT Help Desk Command Center
                </h2>
                <p class="text-sm text-slate-500">
                    Week 3 is now live with ticket structure, category management, and role-aware workflow access.
                </p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-[#dff5fa] px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[#0f7b92]">
                {{ $user->role->label() }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-gradient-to-r from-[#0b5060] to-[#11829a] px-6 py-8 text-white shadow-[0_22px_60px_rgba(6,36,46,0.18)]">
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-cyan-100/80">Workspace Overview</p>
            <h3 class="mt-3 text-3xl font-semibold">Welcome, {{ $user->name }}</h3>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-cyan-50/90">
                Your help desk workspace now includes ticket intake, status tracking, priorities, and centralized categories.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-100">
                    Open Tickets
                </a>

                @if ($user->hasRole(\App\Enums\UserRole::Employee))
                    <a href="{{ route('tickets.create') }}" class="inline-flex items-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                        Create Ticket
                    </a>
                @endif

                @if ($user->isAdmin())
                    <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                        Manage Categories
                    </a>
                @endif
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Visible Tickets</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $ticketCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Tickets available in your current role scope.</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Open Tickets</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $openTickets }}</p>
                <p class="mt-2 text-sm text-slate-500">Requests that still need active follow-up.</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Resolved Tickets</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $resolvedTickets }}</p>
                <p class="mt-2 text-sm text-slate-500">Issues already completed by the support workflow.</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Categories</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $categoryCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Classification options available during ticket creation.</p>
            </div>
        </section>
    </div>
</x-app-layout>
