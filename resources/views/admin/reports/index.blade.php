<x-app-layout>
    <x-slot name="header">
        <div
            x-data="adminReportsPage({
                range: @js($range),
                rangeLabel: @js($rangeLabel),
                generatedAt: @js($generatedAt->format('d M Y, h:i A')),
                exportUrl: @js(route('admin.reports.export.pdf', ['range' => $range])),
                contentHtml: @js(view('admin.reports.partials.content', get_defined_vars())->render()),
            })"
            class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-[#0a6478] px-7 py-7 text-white shadow-[0_20px_56px_rgba(15,23,42,0.22)]"
        >
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.34em] text-cyan-300/90">Reports Center</p>
                    <h2 class="mt-2 text-[1.85rem] font-black leading-tight tracking-tight">Administrative Reports</h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300/80">
                        Operational snapshots covering ticket flow, priorities, team output, and recent activity.
                    </p>
                </div>

                <div class="flex-shrink-0 rounded-2xl border border-white/10 bg-white/8 px-5 py-4 backdrop-blur-sm">
                    <p class="text-[0.62rem] font-bold uppercase tracking-[0.26em] text-cyan-200/70">Current Period</p>
                    <p class="mt-1.5 text-xl font-black leading-tight" x-text="$store.adminReports.rangeLabel || @js($rangeLabel)"></p>
                    <p class="mt-1 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-300/70">
                        Generated <span x-text="$store.adminReports.generatedAt || @js($generatedAt->format('d M Y, h:i A'))"></span>
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div
        x-data="adminReportsPage({
            range: @js($range),
            rangeLabel: @js($rangeLabel),
            generatedAt: @js($generatedAt->format('d M Y, h:i A')),
            exportUrl: @js(route('admin.reports.export.pdf', ['range' => $range])),
            contentHtml: @js(view('admin.reports.partials.content', get_defined_vars())->render()),
        })"
        class="space-y-5"
    >
        <section class="rounded-[1.75rem] border border-slate-200 bg-white px-6 py-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Report Window</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <p class="text-sm font-semibold text-slate-700">Select a range, then export when ready.</p>
                        <span
                            x-show="$store.adminReports.loading"
                            x-transition.opacity
                            class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-[#0f7b92]"
                        >
                            Updating...
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    @foreach ($rangeOptions as $value => $label)
                        <button
                            type="button"
                            @click="$store.adminReports.loadRange('{{ route('admin.reports.index', ['range' => $value]) }}')"
                            :disabled="$store.adminReports.loading"
                            :class="$store.adminReports.range === '{{ $value }}'
                                ? 'bg-[#0f7b92] text-white shadow-sm shadow-cyan-900/20'
                                : 'border border-slate-200 bg-white text-slate-500 hover:border-[#0f7b92]/40 hover:text-[#0f7b92]'"
                            class="inline-flex items-center rounded-full px-4 py-2 text-[0.7rem] font-bold uppercase tracking-[0.18em] transition-all disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ $label }}
                        </button>
                    @endforeach

                    <a
                        :href="$store.adminReports.exportUrl || @js(route('admin.reports.export.pdf', ['range' => $range]))"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#0f7b92] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#0a6478]"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </section>

        <div x-html="$store.adminReports.contentHtml || @js(view('admin.reports.partials.content', get_defined_vars())->render())">
            @include('admin.reports.partials.content')
        </div>
    </div>
</x-app-layout>
