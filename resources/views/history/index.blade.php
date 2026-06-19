<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-[#0f7b92]">System History</p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight text-slate-950">Historical Audit</h2>
                <p class="text-sm text-slate-500">
                    Review the recorded actions across tickets, uploads, authentication, and administration.
                </p>
            </div>

            <span class="inline-flex w-fit items-center rounded-full bg-[#dff5fa] px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-[#0f7b92]">
                {{ $user->hasRole(\App\Enums\UserRole::Employee) ? 'Personal Scope' : 'Global Scope' }}
            </span>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Visible Events</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $actionCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Audit records available in your access scope.</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Today</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">{{ $todayCount }}</p>
                <p class="mt-2 text-sm text-slate-500">New activity captured on {{ now()->format('d M Y') }}.</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Coverage</p>
                <p class="mt-4 text-3xl font-semibold text-slate-950">Live</p>
                <p class="mt-2 text-sm text-slate-500">Authentication, tickets, uploads, users, and categories are tracked.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-xl font-semibold text-slate-950">Activity Stream</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Entries are sorted from newest to oldest for fast review.
                </p>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse ($logs as $log)
                    <article class="px-6 py-5 transition hover:bg-slate-50/70">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-sm font-bold text-cyan-700">
                                    {{ strtoupper(substr($log->actor?->name ?? 'S', 0, 1)) }}
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-950">{{ $log->actor?->name ?? 'System' }}</p>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-slate-600">
                                            {{ str_replace('.', ' ', $log->action) }}
                                        </span>
                                        @if ($log->ticket)
                                            <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-cyan-700">
                                                {{ $log->ticket->ticket_number }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $log->description }}</p>

                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                                        <span>{{ $log->created_at->format('d M Y, h:i A') }}</span>
                                        @if ($log->targetUser)
                                            <span>Target: {{ $log->targetUser->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if ($log->ticket)
                                <div class="xl:pl-6">
                                    <a href="{{ route('tickets.show', $log->ticket) }}" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                        Open Ticket
                                    </a>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <p class="text-lg font-semibold text-slate-900">No audit history available yet.</p>
                        <p class="mt-2 text-sm text-slate-500">The system will start listing activity here as users work inside the platform.</p>
                    </div>
                @endforelse
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
