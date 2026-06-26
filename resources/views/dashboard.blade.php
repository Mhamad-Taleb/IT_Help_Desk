<x-app-layout>
    <x-slot name="header">
        @php
            $scopeLabel = match (true) {
                $user->hasRole(\App\Enums\UserRole::Admin) => 'Full System Access',
                $user->hasRole(\App\Enums\UserRole::Manager) => 'Ticket Oversight',
                $user->hasRole(\App\Enums\UserRole::SupportAgent) => 'Assigned Queue',
                default => 'Personal Workspace',
            };

            $notificationsPayload = $recentNotifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'action_label' => \Illuminate\Support\Str::headline(str_replace('.', ' ', $notification->action)),
                    'description' => $notification->description,
                    'created_at' => $notification->created_at->format('d M, h:i A'),
                    'ticket_number' => $notification->ticket?->ticket_number,
                    'actor_initial' => strtoupper(substr($notification->actor?->name ?? 'S', 0, 1)),
                    'is_read' => (int) $notification->current_user_read_count > 0,
                    'read_url' => route('notifications.read', $notification),
                ];
            })->values();
        @endphp

        <div
            class="dashboard-header-shell relative isolate z-[90] -mb-5 -ml-4 -mr-4 -mt-5 w-auto px-4 py-3 sm:-ml-6 sm:-mr-6 sm:px-6 lg:-ml-8 lg:-mr-8 lg:px-8 xl:-ml-10 xl:-mr-10 xl:px-10"
            x-data="dashboardHeader({
                notifications: @js($notificationsPayload),
                unreadCount: {{ $unreadNotificationCount }},
                markAllReadUrl: '{{ route('notifications.read-all') }}',
                clearAllUrl: '{{ route('notifications.clear-all') }}',
            })"
        >
            <div class="absolute inset-y-0 right-0 hidden w-[30rem] bg-[radial-gradient(circle_at_top_right,rgba(45,212,191,0.18),transparent_58%)] lg:block"></div>

            <div class="relative flex min-h-[5.25rem] flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <p class="text-[1.05rem] font-bold uppercase tracking-[0.34em] text-cyan-600 sm:text-[1.2rem]">
                            IT Help Desk
                        </p>
                       
                    </div>
                </div>

                <div class="dashboard-header-actions flex flex-wrap items-center gap-1.5 self-start rounded-[1.2rem] border border-slate-200/80 bg-white/84 p-1.5 shadow-[0_14px_28px_rgba(15,23,42,0.06)] backdrop-blur lg:self-center">
                    <span class="rounded-full bg-cyan-50 px-3.5 py-2 text-[0.66rem] font-bold uppercase tracking-[0.22em] text-cyan-700">
                        {{ $scopeLabel }}
                    </span>

                    <button
                        type="button"
                        @click="$store.dashboardTheme.toggle()"
                        class="dashboard-top-action h-9 w-9 rounded-[0.95rem]"
                    >
                        <svg x-show="! $store.dashboardTheme.darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z" />
                        </svg>

                        <svg x-show="$store.dashboardTheme.darkMode" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2.25M12 18.75V21M5.636 5.636l1.591 1.591M16.773 16.773l1.591 1.591M3 12h2.25M18.75 12H21M5.636 18.364l1.591-1.591M16.773 7.227l1.591-1.591M15.75 12A3.75 3.75 0 1112 8.25 3.75 3.75 0 0115.75 12z" />
                        </svg>
                    </button>

                    <div class="relative z-[95]" @click.outside="notificationsOpen = false">
                        <button
                            type="button"
                            @click="notificationsOpen = ! notificationsOpen"
                            class="dashboard-top-action relative h-9 w-9 rounded-[0.95rem]"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 10-12 0v.75a8.967 8.967 0 01-2.31 6.022 23.848 23.848 0 005.454 1.31m5.713 0a3 3 0 11-5.713 0" />
                            </svg>

                            <template x-if="unreadCount > 0">
                                <span class="absolute right-2 top-2 flex h-3 w-3">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                                    <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
                                </span>
                            </template>
                        </button>

                        <div
                            x-show="notificationsOpen"
                            x-transition.origin.top.right
                            style="display: none;"
                            class="dashboard-notification-popover absolute right-0 top-[calc(100%+1rem)] z-[120] w-[20rem] rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-2xl sm:w-[24rem]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-cyan-600">
                                        Notifications
                                    </p>
                                    <div class="mt-1 flex items-center gap-3 text-sm">
                                        <button
                                            type="button"
                                            class="font-semibold text-slate-700 underline underline-offset-4 transition hover:text-cyan-700"
                                            @click="markAllAsRead()"
                                        >
                                            Mark all read
                                        </button>
                                        <button
                                            type="button"
                                            class="font-semibold text-slate-700 underline underline-offset-4 transition hover:text-rose-600"
                                            @click="clearAllNotifications()"
                                        >
                                            Clear all
                                        </button>
                                    </div>
                                </div>

                                <span class="rounded-full bg-rose-50 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-rose-600">
                                    <span x-text="unreadCount"></span>&nbsp;unread
                                </span>
                            </div>

                            <div class="mt-4 max-h-[23rem] space-y-3 overflow-y-auto pr-1">
                                <template x-if="notifications.length === 0">
                                    <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                        <p class="text-sm font-semibold text-slate-900">No notifications yet.</p>
                                        <p class="mt-2 text-sm text-slate-500">New activity will appear here as soon as the platform records it.</p>
                                    </div>
                                </template>

                                <template x-for="notification in notifications" :key="notification.id">
                                    <article
                                        class="dashboard-notification-item rounded-[1.25rem] border p-4"
                                        :class="notification.is_read ? 'border-slate-200 bg-white' : 'dashboard-unread-item border-cyan-100 bg-cyan-50/70'"
                                    >
                                        <div class="flex gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white" x-text="notification.actor_initial"></div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                        <p class="text-sm font-semibold text-slate-950" x-text="notification.action_label"></p>

                                                        <template x-if="! notification.is_read">
                                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.18em] text-rose-600">
                                                                New
                                                            </span>
                                                        </template>

                                                        <template x-if="notification.is_read">
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.18em] text-emerald-700">
                                                                Read
                                                            </span>
                                                        </template>
                                                    </div>

                                                    <template x-if="! notification.is_read">
                                                        <button
                                                            type="button"
                                                            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
                                                            @click="markAsRead(notification.id, notification.read_url)"
                                                            title="Mark as read"
                                                            aria-label="Mark as read"
                                                        >
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </button>
                                                    </template>
                                                </div>

                                                <p class="mt-1.5 break-words text-sm leading-6 text-slate-600" x-text="notification.description"></p>

                                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-400">
                                                    <span x-text="notification.created_at"></span>
                                                    <template x-if="notification.ticket_number">
                                                        <span x-text="notification.ticket_number"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $performanceTitle = match (true) {
            $isAdmin => 'Top Employees',
            $isManager => 'Support Performance',
            $isSupportAgent => 'Your Assigned Tickets',
            default => 'Your Recent Tickets',
        };

        $performanceDescription = match (true) {
            $isAdmin => 'Resolved ticket output across the full support team.',
            $isManager => 'Who is closing the most tickets in the current workflow.',
            $isSupportAgent => 'The latest tickets currently routed into your queue.',
            default => 'The latest tickets you have submitted into support.',
        };

        $recentActivityPreview = $recentNotifications->take(2);
        $remainingRecentActivity = $recentNotifications->slice(2)->values();
        $recentActivityModalTitle = $isAdmin ? 'Full Analytics Activity' : ($isManager ? 'All Visible Ticket Activity' : 'All Visible Activity');
        $recentActivityModalDescription = $isAdmin
            ? 'All additional activity entries recorded in your current admin analytics scope.'
            : ($isManager
                ? 'Additional ticket-side activity that is visible to the manager role.'
                : 'Additional activity entries connected to your visible support scope.');
    @endphp

    <div class="dashboard-page-root dashboard-body-shell space-y-6 px-1 py-1">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($roleInsightCards as $card)
                <article class="dashboard-panel rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-[0.7rem] font-bold uppercase tracking-[0.28em] text-[#0f7b92]">{{ $card['label'] }}</p>
                    <p class="mt-4 text-4xl font-black tracking-tight text-slate-950">{{ $card['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $card['caption'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($isAdmin)
            

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
                <article class="dashboard-panel overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Dashboard Analytics</p>
                                <h3 class="mt-2 text-2xl font-black text-slate-950">Tickets Created vs Resolved</h3>
                                <p class="mt-1 text-sm text-slate-500">A 14-day trend showing intake pressure against completed outcomes.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="inline-flex items-center gap-2 rounded-full bg-cyan-50 px-3 py-1.5 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-cyan-700">
                                    <span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span>
                                    Created
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-emerald-700">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    Resolved
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-6">
                        

                        <div class="mt-6 overflow-hidden rounded-[1.7rem] border border-slate-200 bg-slate-50 px-4 py-5">
                            <svg viewBox="0 0 576 240" class="h-[18rem] w-full">
                                @for ($line = 0; $line < 4; $line++)
                                    @php
                                        $y = 18 + ($line * 52);
                                    @endphp
                                    <line x1="8" y1="{{ $y }}" x2="568" y2="{{ $y }}" stroke="currentColor" class="text-slate-200" stroke-dasharray="4 6" />
                                @endfor

                                <polyline fill="none" stroke="#22d3ee" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $trendCreatedPoints }}" />
                                <polyline fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" points="{{ $trendResolvedPoints }}" />

                                @foreach ($trendCreatedValues as $index => $value)
                                    @php
                                        $totalPoints = max(count($trendCreatedValues) - 1, 1);
                                        $x = 8 + ((560 / $totalPoints) * $index);
                                        $createdY = 208 - (($value / max($trendMax, 1)) * 196);
                                        $resolvedValue = $trendResolvedValues[$index] ?? 0;
                                        $resolvedY = 208 - (($resolvedValue / max($trendMax, 1)) * 196);
                                    @endphp

                                    <circle cx="{{ $x }}" cy="{{ $createdY }}" r="4.5" fill="#22d3ee" />
                                    <circle cx="{{ $x }}" cy="{{ $resolvedY }}" r="4.5" fill="#10b981" />
                                @endforeach
                            </svg>

                            <div class="mt-4 grid grid-cols-7 gap-2 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-400">
                                @foreach ($trendLabels as $label)
                                    @if ($loop->index % 2 === 0)
                                        <span>{{ $label }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </article>

                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Status Split</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Ticket Status Distribution</h3>
                        </div>

                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-slate-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-slate-600">
                            <span>{{ $ticketCount }}</span>
                            <span>Total</span>
                        </span>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-6">
                        <div class="relative flex h-56 w-56 items-center justify-center rounded-full" style="background: conic-gradient({{ $statusChartSegments }});">
                            <div class="flex h-36 w-36 flex-col items-center justify-center rounded-full bg-white text-center shadow-inner">
                                <p class="text-[0.65rem] font-bold uppercase tracking-[0.24em] text-slate-400">Visible</p>
                                <p class="mt-2 text-4xl font-black text-slate-950">{{ $ticketCount }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Tickets</p>
                            </div>
                        </div>

                        <div class="w-full space-y-3">
                            @foreach ($statusBreakdown as $status)
                                @php
                                    $colorClass = match ($status['value']) {
                                        'open' => 'bg-cyan-500',
                                        'in_progress' => 'bg-amber-500',
                                        'pending' => 'bg-violet-500',
                                        'resolved' => 'bg-emerald-500',
                                        default => 'bg-slate-500',
                                    };
                                @endphp

                                <div class="flex items-center justify-between gap-3 rounded-[1.1rem] bg-slate-50 px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-3 w-3 rounded-full {{ $colorClass }}"></span>
                                        <span class="text-sm font-semibold text-slate-900">{{ $status['label'] }}</span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-bold text-slate-500">{{ $status['count'] }}</span>
                                        <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-600">{{ $status['percentage'] }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Priority Analytics</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-950">Workload Concentration</h3>
                    <p class="mt-1 text-sm text-slate-500">See where the current ticket pressure is building.</p>

                    <div class="mt-6 space-y-4">
                        @foreach ($priorityBreakdown as $priority)
                            @php
                                $barClass = match ($priority['value']) {
                                    'critical' => 'bg-rose-500',
                                    'high' => 'bg-amber-500',
                                    'medium' => 'bg-sky-500',
                                    default => 'bg-slate-500',
                                };
                            @endphp

                            <div class="rounded-[1.25rem] bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $priority['label'] }}</p>
                                    <span class="text-sm font-black text-slate-950">{{ $priority['count'] }}</span>
                                </div>

                                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full {{ $barClass }}" style="width: {{ max($priority['percentage'], $priority['count'] > 0 ? 8 : 0) }}%"></div>
                                </div>

                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $priority['percentage'] }}% of visible workload</p>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Top Categories</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-950">Issue Concentration</h3>
                    <p class="mt-1 text-sm text-slate-500">The categories generating the highest support demand.</p>

                    <div class="mt-6 space-y-4">
                        @forelse ($categoryDistribution as $category)
                            <div class="rounded-[1.25rem] bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $category['name'] }}</p>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-600">{{ $category['count'] }}</span>
                                </div>

                                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-[#0f7b92]" style="width: {{ max($category['percentage'], $category['count'] > 0 ? 10 : 0) }}%"></div>
                                </div>

                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">{{ $category['percentage'] }}% of visible tickets</p>
                            </div>
                        @empty
                            <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-900">No category data yet.</p>
                                <p class="mt-2 text-sm text-slate-500">Category analytics will appear once tickets are available.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Operational Insight</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-950">Decision Signals</h3>
                    <p class="mt-1 text-sm text-slate-500">High-level indicators to guide next actions inside the help desk.</p>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-[1.4rem] bg-slate-50 p-4">
                            <p class="text-[0.66rem] font-bold uppercase tracking-[0.24em] text-slate-500">Average Resolution Time</p>
                            <p class="mt-3 text-3xl font-black text-slate-950">{{ $averageResolutionLabel }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-[1.4rem] bg-slate-50 p-4">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.24em] text-slate-500">Categories</p>
                                <p class="mt-3 text-2xl font-black text-slate-950">{{ $categoryCount }}</p>
                                <p class="mt-2 text-sm text-slate-500">Available help desk classifications.</p>
                            </div>

                            <div class="rounded-[1.4rem] bg-slate-50 p-4">
                                <p class="text-[0.66rem] font-bold uppercase tracking-[0.24em] text-slate-500">Unread Notifications</p>
                                <p class="mt-3 text-2xl font-black text-slate-950">{{ $unreadNotificationCount }}</p>
                                <p class="mt-2 text-sm text-slate-500">Activity items still waiting for review.</p>
                            </div>
                        </div>

                        <div class="rounded-[1.4rem] border border-cyan-100 bg-cyan-50/70 p-4">
                            <p class="text-[0.66rem] font-bold uppercase tracking-[0.24em] text-[#0f7b92]">Analytics Scope</p>
                            <p class="mt-3 text-sm leading-7 text-slate-700">
                                This dashboard includes platform-wide ticket, category, and performance visibility.
                            </p>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">{{ $performanceTitle }}</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Performance Snapshot</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $performanceDescription }}</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($teamPerformance as $member)
                            <div class="flex items-center justify-between gap-4 rounded-[1.3rem] bg-slate-50 px-4 py-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $member['name'] }}</p>
                                    <p class="mt-1 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $member['role'] }}</p>
                                </div>

                                <div class="text-right">
                                    <p class="text-2xl font-black text-slate-950">{{ $member['resolved_count'] }}</p>
                                    <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">Resolved</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-900">No performance data yet.</p>
                                <p class="mt-2 text-sm text-slate-500">Resolved ticket output will appear here once the team closes tickets.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Recent Activity</p>
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-2xl font-black text-slate-950">Analytics Feed</h3>

                                <div class="flex items-center gap-3">
                                    @if ($remainingRecentActivity->isNotEmpty())
                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'recent-activity-modal')"
                                            class="whitespace-nowrap text-xs font-bold uppercase tracking-[0.2em] text-[#0f7b92] underline underline-offset-4 transition hover:text-cyan-700"
                                        >
                                            Show more
                                        </button>
                                    @endif

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-slate-600">
                                        {{ $notificationCount }} Today
                                    </span>
                                </div>
                            </div>

                            <p class="mt-1 text-sm text-slate-500">A quick view of the latest actions shaping the current ticket flow.</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($recentActivityPreview as $notification)
                            <article class="rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
                                        {{ strtoupper(substr($notification->actor?->name ?? 'S', 0, 1)) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $notification->action)) }}
                                            </p>
                                            @if ($notification->ticket)
                                                <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-600">
                                                    {{ $notification->ticket->ticket_number }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->description }}</p>
                                        <p class="mt-2 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-900">No recent activity yet.</p>
                                <p class="mt-2 text-sm text-slate-500">New ticket actions will begin populating this feed automatically.</p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>

        @else
            @if ($isManager)
                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                    <article class="dashboard-panel overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Manager Overview</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">Recent Ticket Movement</h3>
                            <p class="mt-1 text-sm text-slate-500">Track the latest visible tickets, ownership, and workflow progress.</p>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @forelse ($recentTickets as $ticket)
                                <article class="px-6 py-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-cyan-50 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-cyan-700">{{ $ticket->ticket_number }}</span>
                                                <span class="rounded-full px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] {{ $ticket->status->badgeClasses() }}">{{ $ticket->status->label() }}</span>
                                            </div>

                                            <p class="mt-3 text-lg font-bold text-slate-950">{{ $ticket->title }}</p>
                                            <p class="mt-2 text-sm text-slate-500">
                                                Created by {{ $ticket->creator?->name ?? 'Unknown' }} · Assigned to {{ $ticket->assignee?->name ?? 'Unassigned' }}
                                            </p>
                                        </div>

                                        <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex w-fit items-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                            Open Ticket
                                        </a>
                                    </div>
                                </article>
                            @empty
                                <div class="px-6 py-12 text-center">
                                    <p class="text-sm font-semibold text-slate-900">No visible tickets yet.</p>
                                    <p class="mt-2 text-sm text-slate-500">Once tickets enter the workflow, they will appear here.</p>
                                </div>
                            @endforelse
                        </div>
                    </article>

                    <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Workflow Status</p>
                        <h3 class="mt-2 text-2xl font-black text-slate-950">Ticket Status Split</h3>

                        <div class="mt-6 space-y-3">
                            @foreach ($statusBreakdown as $status)
                                @php
                                    $colorClass = match ($status['value']) {
                                        'open' => 'bg-cyan-500',
                                        'in_progress' => 'bg-amber-500',
                                        'pending' => 'bg-violet-500',
                                        'resolved' => 'bg-emerald-500',
                                        default => 'bg-slate-500',
                                    };
                                @endphp

                                <div class="flex items-center justify-between gap-3 rounded-[1.1rem] bg-slate-50 px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="h-3 w-3 rounded-full {{ $colorClass }}"></span>
                                        <span class="text-sm font-semibold text-slate-900">{{ $status['label'] }}</span>
                                    </div>

                                    <span class="text-sm font-bold text-slate-500">{{ $status['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>
            @endif

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">{{ $performanceTitle }}</p>
                            <h3 class="mt-2 text-2xl font-black text-slate-950">{{ $isManager ? 'Visible Ticket Queue' : 'Operational Focus' }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $performanceDescription }}</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($recentTickets as $ticket)
                            <div class="rounded-[1.3rem] bg-slate-50 px-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $ticket->title }}</p>
                                        <p class="mt-1 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $ticket->ticket_number }}</p>
                                        @if ($isManager)
                                            <p class="mt-2 text-sm text-slate-500">
                                                {{ $ticket->creator?->name ?? 'Unknown' }} · {{ $ticket->assignee?->name ?? 'Unassigned' }}
                                            </p>
                                        @endif
                                    </div>

                                    <span class="rounded-full px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] {{ $ticket->status->badgeClasses() }}">
                                        {{ $ticket->status->label() }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-900">No tickets in scope yet.</p>
                                <p class="mt-2 text-sm text-slate-500">Once tickets enter your visible queue, they will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="dashboard-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[#0f7b92]">Recent Activity</p>
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-2xl font-black text-slate-950">{{ $isManager ? 'Ticket Activity Feed' : 'My Activity Feed' }}</h3>

                                <div class="flex items-center gap-3">
                                    @if ($remainingRecentActivity->isNotEmpty())
                                        <button
                                            type="button"
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'recent-activity-modal')"
                                            class="whitespace-nowrap text-xs font-bold uppercase tracking-[0.2em] text-[#0f7b92] underline underline-offset-4 transition hover:text-cyan-700"
                                        >
                                            Show more
                                        </button>
                                    @endif

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-slate-600">
                                        {{ $notificationCount }} Today
                                    </span>
                                </div>
                            </div>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ $isManager ? 'Only ticket-side events visible to the manager role appear here.' : 'Only updates connected to your visible tickets appear here.' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse ($recentActivityPreview as $notification)
                            <article class="rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
                                        {{ strtoupper(substr($notification->actor?->name ?? 'S', 0, 1)) }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $notification->action)) }}
                                            </p>
                                            @if ($notification->ticket)
                                                <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-600">
                                                    {{ $notification->ticket->ticket_number }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->description }}</p>
                                        <p class="mt-2 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.4rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                                <p class="text-sm font-semibold text-slate-900">No recent activity yet.</p>
                                <p class="mt-2 text-sm text-slate-500">New ticket actions will begin populating this feed automatically.</p>
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        @endif
    </div>

    @if ($remainingRecentActivity->isNotEmpty())
        <x-modal name="recent-activity-modal" maxWidth="2xl" focusable>
            <div class="rounded-[1.7rem] bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-[#0f7b92]">
                            Recent Activity
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-slate-950">
                            {{ $recentActivityModalTitle }}
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $recentActivityModalDescription }}
                        </p>
                    </div>

                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('close-modal', 'recent-activity-modal')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50"
                    >
                        Close
                    </button>
                </div>

                <div class="mt-6 max-h-[32rem] space-y-3 overflow-y-auto pr-1">
                    @foreach ($remainingRecentActivity as $notification)
                        <article class="rounded-[1.3rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white">
                                    {{ strtoupper(substr($notification->actor?->name ?? 'S', 0, 1)) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $notification->action)) }}
                                        </p>
                                        @if ($notification->ticket)
                                            <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-600">
                                                {{ $notification->ticket->ticket_number }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->description }}</p>
                                    <p class="mt-2 text-[0.68rem] font-bold uppercase tracking-[0.18em] text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </x-modal>
    @endif

</x-app-layout>
