<div class="space-y-5">
    <section class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @php
            $kpis = [
                ['label' => 'Total Tickets', 'value' => $ticketCount, 'sub' => 'In the selected window'],
                ['label' => 'Active Queue', 'value' => $activeTickets, 'sub' => 'Open / In Progress / Pending'],
                ['label' => 'Critical', 'value' => $criticalTickets, 'sub' => 'Highest urgency only'],
                ['label' => 'Avg. Resolution', 'value' => $averageResolutionLabel, 'sub' => 'Closed tickets only', 'small' => true],
            ];
        @endphp

        @foreach ($kpis as $kpi)
            <article class="group rounded-[1.6rem] border border-slate-200 bg-white px-6 py-5 shadow-sm transition hover:border-[#0f7b92]/30 hover:shadow-md">
                <p class="text-[0.63rem] font-bold uppercase tracking-[0.28em] text-slate-400">{{ $kpi['label'] }}</p>
                <p class="mt-3 font-black leading-none text-slate-950 {{ ($kpi['small'] ?? false) ? 'text-2xl' : 'text-4xl' }}">
                    {{ $kpi['value'] }}
                </p>
                <p class="mt-2 text-[0.72rem] text-slate-400">{{ $kpi['sub'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Status Distribution</p>
                    <h3 class="mt-1.5 text-xl font-black text-slate-950">Workflow Snapshot</h3>
                </div>
                <span class="mt-0.5 flex-shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-500">
                    {{ $ticketCount }} total
                </span>
            </div>

            <div class="space-y-2">
                @php
                    $statusColors = [
                        'open' => 'bg-sky-500',
                        'in_progress' => 'bg-amber-400',
                        'pending' => 'bg-orange-400',
                        'resolved' => 'bg-emerald-500',
                        'closed' => 'bg-slate-400',
                    ];
                @endphp
                @foreach ($statusBreakdown as $status)
                    @php $dotColor = $statusColors[strtolower(str_replace(' ', '_', $status['label']))] ?? 'bg-slate-400'; @endphp
                    <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3.5 transition hover:bg-slate-100/70">
                        <span class="h-2 w-2 flex-shrink-0 rounded-full {{ $dotColor }}"></span>
                        <p class="flex-1 text-sm font-semibold text-slate-800">{{ $status['label'] }}</p>
                        <span class="text-sm font-black text-slate-950">{{ $status['count'] }}</span>
                        <span class="w-12 text-right text-[0.68rem] font-bold text-slate-400">{{ $status['percentage'] }}%</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Priority Distribution</p>
                    <h3 class="mt-1.5 text-xl font-black text-slate-950">Urgency Breakdown</h3>
                </div>
                <span class="mt-0.5 flex-shrink-0 rounded-full bg-red-50 px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-red-500">
                    {{ $criticalTickets }} critical
                </span>
            </div>

            <div class="space-y-2">
                @php
                    $priorityColors = [
                        'critical' => 'bg-red-500',
                        'high' => 'bg-orange-400',
                        'medium' => 'bg-amber-400',
                        'low' => 'bg-emerald-400',
                    ];
                @endphp
                @foreach ($priorityBreakdown as $priority)
                    @php $dotColor = $priorityColors[strtolower($priority['label'])] ?? 'bg-slate-400'; @endphp
                    <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3.5 transition hover:bg-slate-100/70">
                        <span class="h-2 w-2 flex-shrink-0 rounded-full {{ $dotColor }}"></span>
                        <p class="flex-1 text-sm font-semibold text-slate-800">{{ $priority['label'] }}</p>
                        <span class="text-sm font-black text-slate-950">{{ $priority['count'] }}</span>
                        <span class="w-12 text-right text-[0.68rem] font-bold text-slate-400">{{ $priority['percentage'] }}%</span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Category Report</p>
                <h3 class="mt-1.5 text-xl font-black text-slate-950">Top Categories</h3>
            </div>

            <div class="space-y-2">
                @forelse ($categoryBreakdown as $index => $category)
                    <div class="flex items-center gap-4 rounded-2xl bg-slate-50 px-4 py-3.5 transition hover:bg-slate-100/70">
                        <span class="w-5 flex-shrink-0 text-center text-[0.65rem] font-black tabular-nums text-slate-300">
                            {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <p class="flex-1 text-sm font-semibold text-slate-800">{{ $category['name'] }}</p>
                        <span class="text-sm font-black text-slate-950">{{ $category['count'] }}</span>
                        <span class="w-12 text-right text-[0.68rem] font-bold text-slate-400">{{ $category['percentage'] }}%</span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">No category data yet.</p>
                        <p class="mt-1 text-sm text-slate-400">Categories appear as tickets are logged and assigned.</p>
                    </div>
                @endforelse
            </div>
        </article>

        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Performance Report</p>
                <h3 class="mt-1.5 text-xl font-black text-slate-950">Support Output</h3>
            </div>

            <div class="space-y-2">
                @forelse ($teamPerformance as $member)
                    <div class="flex items-center gap-4 rounded-2xl bg-slate-50 px-4 py-3.5 transition hover:bg-slate-100/70">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-slate-900 text-[0.65rem] font-black text-white">
                            {{ strtoupper(substr($member['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $member['name'] }}</p>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $member['role'] }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-xl font-black text-slate-950">{{ $member['resolved_count'] }}</p>
                            <p class="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-slate-400">resolved</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">No performance data yet.</p>
                        <p class="mt-1 text-sm text-slate-400">Resolved counts appear once tickets are closed.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Recent Activity</p>
                <h3 class="mt-1.5 text-xl font-black text-slate-950">Tracked Events</h3>
                <p class="mt-1 text-sm text-slate-400">Ticket actions recorded within the selected period.</p>
            </div>
            <span class="flex-shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-500">
                {{ $activityCount }} events
            </span>
        </div>

        <div class="relative">
            <div class="absolute bottom-0 left-[1.1rem] top-0 w-px bg-slate-200"></div>

            <div class="space-y-1 pl-10">
                @forelse ($recentActivity as $activity)
                    <div class="relative py-3">
                        <span class="absolute -left-[2.35rem] top-4 h-2.5 w-2.5 rounded-full border-2 border-white bg-[#0f7b92] shadow-sm ring-2 ring-[#0f7b92]/20"></span>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3.5 transition hover:border-slate-200 hover:bg-white">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $activity->action)) }}
                                </p>
                                @if ($activity->ticket)
                                    <span class="rounded-full border border-[#0f7b92]/20 bg-cyan-50 px-2.5 py-0.5 text-[0.62rem] font-bold uppercase tracking-[0.16em] text-[#0f7b92]">
                                        {{ $activity->ticket->ticket_number }}
                                    </span>
                                @endif
                            </div>

                            <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $activity->description }}</p>
                            <p class="mt-2 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-400">
                                {{ $activity->created_at->format('d M Y, h:i A') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-slate-700">No activity in this period.</p>
                        <p class="mt-1 text-sm text-slate-400">Try a wider range to see more events.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
