<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->hasRole(UserRole::Admin);
        $isManager = $user->hasRole(UserRole::Manager);
        $isSupportAgent = $user->hasRole(UserRole::SupportAgent);
        $isEmployee = $user->hasRole(UserRole::Employee);

        $ticketScope = Ticket::query()->visibleTo($user);
        $notificationScope = AuditLog::query()->notificationVisibleTo($user);
        $activeStatuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::Pending->value,
        ];
        $completedStatuses = [
            TicketStatus::Resolved->value,
            TicketStatus::Closed->value,
        ];
        $ticketCount = (clone $ticketScope)->count();
        $statusCounts = (clone $ticketScope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $priorityCounts = (clone $ticketScope)
            ->selectRaw('priority, COUNT(*) as aggregate')
            ->groupBy('priority')
            ->pluck('aggregate', 'priority');
        $statusBreakdown = collect(TicketStatus::cases())->map(
            fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
                'percentage' => $ticketCount > 0
                    ? (int) round((((int) ($statusCounts[$status->value] ?? 0)) / $ticketCount) * 100)
                    : 0,
            ]
        );
        $priorityBreakdown = collect(TicketPriority::cases())->map(
            fn (TicketPriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
                'count' => (int) ($priorityCounts[$priority->value] ?? 0),
                'percentage' => $ticketCount > 0
                    ? (int) round((((int) ($priorityCounts[$priority->value] ?? 0)) / $ticketCount) * 100)
                    : 0,
            ]
        );
        $trendStart = CarbonImmutable::now()->subDays(13)->startOfDay();
        $trendEnd = CarbonImmutable::now()->endOfDay();
        $trendCreatedCounts = (clone $ticketScope)
            ->selectRaw('DATE(created_at) as metric_date, COUNT(*) as aggregate')
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->groupBy('metric_date')
            ->pluck('aggregate', 'metric_date');
        $trendResolvedCounts = (clone $ticketScope)
            ->selectRaw('DATE(COALESCE(closed_at, resolved_at)) as metric_date, COUNT(*) as aggregate')
            ->where(function ($query) use ($trendStart, $trendEnd): void {
                $query
                    ->whereBetween('resolved_at', [$trendStart, $trendEnd])
                    ->orWhereBetween('closed_at', [$trendStart, $trendEnd]);
            })
            ->groupBy('metric_date')
            ->pluck('aggregate', 'metric_date');
        $trendDays = collect(range(0, 13))
            ->map(fn (int $dayOffset) => $trendStart->addDays($dayOffset));
        $createdTrendValues = $trendDays
            ->map(fn (CarbonImmutable $day): int => (int) ($trendCreatedCounts[$day->toDateString()] ?? 0))
            ->all();
        $resolvedTrendValues = $trendDays
            ->map(fn (CarbonImmutable $day): int => (int) ($trendResolvedCounts[$day->toDateString()] ?? 0))
            ->all();
        $trendMax = max(1, max($createdTrendValues), max($resolvedTrendValues));
        $categoryDistribution = (clone $ticketScope)
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, COUNT(*) as aggregate')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'count' => (int) $row->aggregate,
                'percentage' => $ticketCount > 0 ? (int) round(($row->aggregate / $ticketCount) * 100) : 0,
            ]);
        $averageResolutionHours = (clone $ticketScope)
            ->where(function ($query): void {
                $query->whereNotNull('resolved_at')->orWhereNotNull('closed_at');
            })
            ->get(['created_at', 'resolved_at', 'closed_at'])
            ->map(function (Ticket $ticket): float {
                $endAt = $ticket->closed_at ?? $ticket->resolved_at;

                return $endAt
                    ? $ticket->created_at->diffInMinutes($endAt) / 60
                    : 0;
            })
            ->filter(fn (float $hours): bool => $hours > 0)
            ->avg();
        $createdThisMonth = (clone $ticketScope)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $resolvedThisMonth = (clone $ticketScope)
            ->where(function ($query): void {
                $query
                    ->whereBetween('resolved_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->orWhereBetween('closed_at', [now()->startOfMonth(), now()->endOfMonth()]);
            })
            ->count();
        $completedByAssignee = (clone $ticketScope)
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw('users.name as name, users.role as role, COUNT(*) as aggregate')
            ->whereNotNull('tickets.assigned_to')
            ->whereIn('tickets.status', $completedStatuses)
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'role' => UserRole::from($row->role)->label(),
                'resolved_count' => (int) $row->aggregate,
            ]);
        $recentNotifications = (clone $notificationScope)
            ->with(['actor:id,name', 'ticket:id,ticket_number'])
            ->withCount([
                'readByUsers as current_user_read_count' => fn ($query) => $query->where('users.id', $user->id),
            ])
            ->latest()
            ->limit(6)
            ->get();
        $roleInsightCards = $this->buildRoleInsightCards(
            $user->role,
            $ticketCount,
            (int) ($statusCounts[TicketStatus::Open->value] ?? 0),
            (int) ($statusCounts[TicketStatus::Resolved->value] ?? 0),
            (clone $ticketScope)->whereIn('status', $activeStatuses)->count(),
            (clone $ticketScope)->where('assigned_to', $user->id)->whereIn('status', $activeStatuses)->count(),
            (clone $ticketScope)->whereNull('assigned_to')->whereIn('status', $activeStatuses)->count(),
            $recentNotifications->count(),
            $createdThisMonth,
            $resolvedThisMonth
        );
        $statusChartSegments = $this->buildDonutSegments($statusBreakdown);
        $trendLabels = $trendDays->map(fn (CarbonImmutable $day): string => $day->format('d M'))->all();
        return view('dashboard', [
            'categoryCount' => Category::query()->count(),
            'openTickets' => (int) ($statusCounts[TicketStatus::Open->value] ?? 0),
            'resolvedTickets' => (int) ($statusCounts[TicketStatus::Resolved->value] ?? 0),
            'ticketCount' => $ticketCount,
            'activeTicketCount' => (clone $ticketScope)->whereIn('status', $activeStatuses)->count(),
            'urgentTicketCount' => (clone $ticketScope)
                ->whereIn('priority', [
                    TicketPriority::High->value,
                    TicketPriority::Critical->value,
                ])
                ->whereIn('status', $activeStatuses)
                ->count(),
            'newTodayCount' => (clone $ticketScope)->whereDate('created_at', today())->count(),
            'resolvedThisWeekCount' => (clone $ticketScope)
                ->where(function ($query): void {
                    $query
                        ->whereBetween('resolved_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->orWhereBetween('closed_at', [now()->startOfWeek(), now()->endOfWeek()]);
                })
                ->count(),
            'unassignedTicketCount' => (clone $ticketScope)
                ->whereNull('assigned_to')
                ->whereIn('status', $activeStatuses)
                ->count(),
            'assignedToMeCount' => (clone $ticketScope)
                ->where('assigned_to', $user->id)
                ->whereIn('status', $activeStatuses)
                ->count(),
            'notificationCount' => (clone $notificationScope)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'unreadNotificationCount' => (clone $notificationScope)
                ->whereDoesntHave('readByUsers', fn ($query) => $query->where('users.id', $user->id))
                ->count(),
            'statusBreakdown' => $statusBreakdown,
            'priorityBreakdown' => $priorityBreakdown,
            'recentNotifications' => $recentNotifications,
            'recentTickets' => (clone $ticketScope)
                ->with([
                    'category:id,name',
                    'creator:id,name',
                    'assignee:id,name',
                ])
                ->latest()
                ->limit(5)
                ->get(),
            'isAdmin' => $isAdmin,
            'isManager' => $isManager,
            'isSupportAgent' => $isSupportAgent,
            'isEmployee' => $isEmployee,
            'createdThisMonth' => $createdThisMonth,
            'resolvedThisMonth' => $resolvedThisMonth,
            'averageResolutionLabel' => $this->formatResolutionTime($averageResolutionHours),
            'categoryDistribution' => $categoryDistribution,
            'teamPerformance' => $completedByAssignee,
            'roleInsightCards' => $roleInsightCards,
            'statusChartSegments' => $statusChartSegments,
            'trendLabels' => $trendLabels,
            'trendCreatedValues' => $createdTrendValues,
            'trendResolvedValues' => $resolvedTrendValues,
            'trendCreatedPoints' => $this->buildTrendPoints($createdTrendValues, $trendMax),
            'trendResolvedPoints' => $this->buildTrendPoints($resolvedTrendValues, $trendMax),
            'trendMax' => $trendMax,
            'user' => $user,
        ]);
    }

    private function buildTrendPoints(array $values, int $maxValue): string
    {
        if (count($values) === 0) {
            return '';
        }

        $chartWidth = 560;
        $chartHeight = 220;
        $leftPadding = 8;
        $bottomPadding = 12;
        $usableWidth = $chartWidth - ($leftPadding * 2);
        $usableHeight = $chartHeight - $bottomPadding;
        $steps = max(count($values) - 1, 1);

        return collect($values)
            ->map(function (int $value, int $index) use ($maxValue, $leftPadding, $usableWidth, $usableHeight, $steps): string {
                $x = $leftPadding + (($usableWidth / $steps) * $index);
                $y = $usableHeight - (($value / max($maxValue, 1)) * ($usableHeight - 12));

                return round($x, 2).','.round($y, 2);
            })
            ->implode(' ');
    }

    private function formatResolutionTime(?float $hours): string
    {
        if ($hours === null) {
            return 'No resolved data yet';
        }

        if ($hours < 24) {
            return number_format($hours, 1).' Hours';
        }

        return number_format($hours / 24, 1).' Days';
    }

    private function buildDonutSegments(Collection $statusBreakdown): string
    {
        $colors = [
            TicketStatus::Open->value => '#22d3ee',
            TicketStatus::InProgress->value => '#f59e0b',
            TicketStatus::Pending->value => '#8b5cf6',
            TicketStatus::Resolved->value => '#10b981',
            TicketStatus::Closed->value => '#64748b',
        ];

        $currentOffset = 0;

        return $statusBreakdown
            ->filter(fn (array $status): bool => $status['count'] > 0)
            ->map(function (array $status) use (&$currentOffset, $colors): string {
                $start = $currentOffset;
                $end = $currentOffset + $status['percentage'];
                $currentOffset = $end;

                return ($colors[$status['value']] ?? '#cbd5e1').' '.$start.'% '.$end.'%';
            })
            ->whenEmpty(fn (Collection $collection) => $collection->push('#cbd5e1 0% 100%'))
            ->implode(', ');
    }

    private function buildRoleInsightCards(
        UserRole $role,
        int $ticketCount,
        int $openTickets,
        int $resolvedTickets,
        int $activeTickets,
        int $assignedToMe,
        int $unassignedTickets,
        int $recentActivityCount,
        int $createdThisMonth,
        int $resolvedThisMonth
    ): array {
        return match ($role) {
            UserRole::Admin => [
                ['label' => 'Total Tickets', 'value' => $ticketCount, 'caption' => 'Full platform visibility across every queue.'],
                ['label' => 'Open Queue', 'value' => $openTickets, 'caption' => 'Tickets still waiting for action or ownership.'],
                ['label' => 'Resolved This Month', 'value' => $resolvedThisMonth, 'caption' => 'Completed outcomes delivered this month.'],
                ['label' => 'Recent Activity', 'value' => $recentActivityCount, 'caption' => 'Tracked events recorded in the last 24 hours.'],
            ],
            UserRole::Manager => [
                ['label' => 'Visible Tickets', 'value' => $ticketCount, 'caption' => 'All ticket records available for oversight.'],
                ['label' => 'Active Workflow', 'value' => $activeTickets, 'caption' => 'Open, in-progress, and pending tickets in motion.'],
                ['label' => 'Created This Month', 'value' => $createdThisMonth, 'caption' => 'New requests entering the help desk this month.'],
                ['label' => 'Resolved Output', 'value' => $resolvedThisMonth, 'caption' => 'Tickets completed by the support flow this month.'],
            ],
            UserRole::SupportAgent => [
                ['label' => 'Assigned To You', 'value' => $assignedToMe, 'caption' => 'Tickets currently routed to your queue.'],
                ['label' => 'Open In Queue', 'value' => $openTickets, 'caption' => 'Items still waiting for a first or next action.'],
                ['label' => 'Resolved In Scope', 'value' => $resolvedTickets, 'caption' => 'Completed tickets visible in your assigned scope.'],
                ['label' => 'Recent Activity', 'value' => $recentActivityCount, 'caption' => 'New ticket-side activity logged in the last 24 hours.'],
            ],
            UserRole::Employee => [
                ['label' => 'My Tickets', 'value' => $ticketCount, 'caption' => 'Support requests you have opened so far.'],
                ['label' => 'Open Requests', 'value' => $openTickets, 'caption' => 'Your tickets still waiting for support action.'],
                ['label' => 'Resolved Requests', 'value' => $resolvedTickets, 'caption' => 'Your issues already completed by the team.'],
                ['label' => 'Recent Updates', 'value' => $recentActivityCount, 'caption' => 'Fresh notifications linked to your tickets.'],
            ],
        };
    }
}
