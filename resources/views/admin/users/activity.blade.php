<x-app-layout>
    <x-slot name="header">
        {{-- ── User Profile Header ──────────────────────────────────── --}}
        <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-[#0a6478] px-7 py-7 text-white shadow-[0_20px_56px_rgba(15,23,42,0.22)]">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

                {{-- Identity --}}
                <div class="flex items-center gap-5">
                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 text-2xl font-black text-white ring-2 ring-white/15">
                        {{ strtoupper(substr($managedUser->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-[0.63rem] font-bold uppercase tracking-[0.34em] text-cyan-300/90">User Activity</p>
                        <h2 class="mt-1 text-[1.75rem] font-black leading-tight tracking-tight">{{ $managedUser->name }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2.5">
                            <span class="rounded-full bg-white/10 px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.22em] text-cyan-100">
                                {{ $managedUser->role->label() }}
                            </span>
                            <span class="text-sm text-slate-300/70">{{ '@' . $managedUser->username }}</span>
                            <span class="text-slate-500/60">·</span>
                            <span class="text-sm text-slate-300/70">{{ $managedUser->email }}</span>
                        </div>
                    </div>
                </div>

                {{-- Back action --}}
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-5 py-2.5 text-sm font-bold text-white backdrop-blur-sm transition hover:bg-white/15"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    All Users
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">

        {{-- ── KPI Strip ────────────────────────────────────────────── --}}
        <section class="grid grid-cols-3 gap-4">
            @php
                $stats = [
                    ['label' => 'Linked Tickets',  'value' => $ticketCount,       'sub' => 'By role scope'],
                    ['label' => 'Active Tickets',   'value' => $activeTicketCount, 'sub' => 'Open · In Progress · Pending'],
                    ['label' => 'Activity Logs',    'value' => $activityCount,     'sub' => 'Recorded actions'],
                ];
            @endphp
            @foreach ($stats as $stat)
                <article class="rounded-[1.6rem] border border-slate-200 bg-white px-6 py-5 shadow-sm transition hover:border-[#0f7b92]/30 hover:shadow-md">
                    <p class="text-[0.63rem] font-bold uppercase tracking-[0.28em] text-slate-400">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-4xl font-black leading-none text-slate-950">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-[0.72rem] text-slate-400">{{ $stat['sub'] }}</p>
                </article>
            @endforeach
        </section>

        {{-- ── Linked Tickets ───────────────────────────────────────── --}}
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Ticket Scope</p>
                <h3 class="mt-1.5 text-xl font-black text-slate-950">Linked Tickets</h3>
                <p class="mt-1 text-sm text-slate-400">
                    Tickets connected to this account via their {{ strtolower($managedUser->role->label()) }} role.
                </p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($linkedTickets as $ticket)
                    <article class="px-6 py-4 transition hover:bg-slate-50/60">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">

                            {{-- Left: ticket info --}}
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-lg bg-[#eef6fb] px-2.5 py-1 text-[0.62rem] font-bold uppercase tracking-[0.22em] text-[#0f7b92]">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                    <h4 class="text-sm font-bold text-slate-950">{{ $ticket->title }}</h4>
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.72rem] text-slate-400">
                                    <span>{{ $ticket->category?->name ?? 'Uncategorized' }}</span>
                                    <span class="text-slate-300">·</span>
                                    <span>{{ $ticket->creator?->name ?? 'Unknown' }}</span>
                                    <span class="text-slate-300">·</span>
                                    <span>{{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                                </div>
                            </div>

                            {{-- Right: badges + action --}}
                            <div class="flex flex-wrap items-center gap-2 xl:flex-shrink-0 xl:justify-end">
                                <span class="rounded-full px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em]
                                    {{ match ($ticket->priority->value) {
                                        'low'      => 'bg-slate-100 text-slate-600',
                                        'medium'   => 'bg-sky-100 text-sky-700',
                                        'high'     => 'bg-amber-100 text-amber-700',
                                        default    => 'bg-rose-100 text-rose-700',
                                    } }}">
                                    {{ $ticket->priority->label() }}
                                </span>

                                <span class="rounded-full px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em]
                                    {{ match ($ticket->status->value) {
                                        'open'        => 'bg-cyan-100 text-cyan-700',
                                        'in_progress' => 'bg-amber-100 text-amber-700',
                                        'pending'     => 'bg-violet-100 text-violet-700',
                                        'resolved'    => 'bg-emerald-100 text-emerald-700',
                                        default       => 'bg-slate-100 text-slate-600',
                                    } }}">
                                    {{ $ticket->status->label() }}
                                </span>

                                <a
                                    href="{{ route('tickets.show', $ticket) }}"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0f7b92] px-3.5 py-2 text-[0.72rem] font-bold text-white transition hover:bg-[#0a6478]"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                    Open
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="text-sm font-semibold text-slate-700">No linked tickets.</p>
                        <p class="mt-1 text-sm text-slate-400">This user has no tickets connected to their current role.</p>
                    </div>
                @endforelse
            </div>

            @if ($linkedTickets->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $linkedTickets->links() }}
                </div>
            @endif
        </section>

        {{-- ── Activity Stream ──────────────────────────────────────── --}}
        <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Audit Log</p>
                <h3 class="mt-1.5 text-xl font-black text-slate-950">Activity Stream</h3>
                <p class="mt-1 text-sm text-slate-400">
                    All recorded actions connected to this account or their tickets.
                </p>
            </div>

            {{-- Log table --}}
            <div class="divide-y divide-slate-100">
                @forelse ($activityLogs as $log)
                    <article class="group flex gap-0 transition hover:bg-slate-50/50">

                        {{-- Left column: timestamp + action label --}}
                        <div class="w-56 flex-shrink-0 border-r-2 border-[#0f7b92]/15 px-6 py-4 group-hover:border-[#0f7b92]/30">
                            <p class="text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-400">
                                {{ $log->created_at->format('d M Y') }}
                            </p>
                            <p class="text-[0.62rem] text-slate-400">
                                {{ $log->created_at->format('h:i A') }}
                            </p>
                            <p class="mt-2 rounded-md bg-slate-100 px-2 py-1 text-[0.6rem] font-bold uppercase tracking-[0.16em] text-slate-600 inline-block">
                                {{ str_replace('.', ' › ', $log->action) }}
                            </p>
                        </div>

                        {{-- Right column: actor + description + links --}}
                        <div class="min-w-0 flex-1 px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-slate-900">{{ $log->actor?->name ?? 'System' }}</span>
                                @if ($log->ticket)
                                    <span class="rounded-full border border-[#0f7b92]/20 bg-cyan-50 px-2.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.18em] text-[#0f7b92]">
                                        {{ $log->ticket->ticket_number }}
                                    </span>
                                @endif
                                @if ($log->targetUser)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.18em] text-slate-500">
                                        → {{ $log->targetUser->name }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $log->description }}</p>

                            @if ($log->ticket)
                                <a
                                    href="{{ route('tickets.show', $log->ticket) }}"
                                    class="mt-2.5 inline-flex items-center gap-1 text-[0.68rem] font-bold uppercase tracking-[0.16em] text-[#0f7b92] transition hover:text-[#0a6478]"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                    Open Ticket
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <p class="text-sm font-semibold text-slate-700">No activity recorded yet.</p>
                        <p class="mt-1 text-sm text-slate-400">Actions will appear here as this user interacts with the system.</p>
                    </div>
                @endforelse
            </div>

            @if ($activityLogs->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $activityLogs->links() }}
                </div>
            @endif
        </section>

    </div>
</x-app-layout>