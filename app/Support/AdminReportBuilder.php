<?php

namespace App\Support;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminReportBuilder
{
    /**
     * @return array<string, string>
     */
    public static function rangeOptions(): array
    {
        return [
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            'this_month' => 'This Month',
            'all' => 'All Time',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $range = '30d'): array
    {
        $resolvedRange = $this->resolveRange($range);
        $ticketScope = Ticket::query()
            ->with(['category:id,name', 'creator:id,name', 'assignee:id,name'])
            ->when(
                $resolvedRange['start'] && $resolvedRange['end'],
                fn (Builder $query) => $query->whereBetween('tickets.created_at', [$resolvedRange['start'], $resolvedRange['end']])
            );
        $activityScope = AuditLog::query()
            ->notificationEligible()
            ->with(['actor:id,name', 'ticket:id,ticket_number'])
            ->when(
                $resolvedRange['start'] && $resolvedRange['end'],
                fn (Builder $query) => $query->whereBetween('created_at', [$resolvedRange['start'], $resolvedRange['end']])
            );

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
        $categoryBreakdown = (clone $ticketScope)
            ->join('categories', 'tickets.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, COUNT(*) as aggregate')
            ->when(
                $resolvedRange['start'] && $resolvedRange['end'],
                fn (Builder $query) => $query->whereBetween('tickets.created_at', [$resolvedRange['start'], $resolvedRange['end']])
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'count' => (int) $row->aggregate,
                'percentage' => $ticketCount > 0 ? (int) round(($row->aggregate / $ticketCount) * 100) : 0,
            ]);

        $completedStatuses = [
            TicketStatus::Resolved->value,
            TicketStatus::Closed->value,
        ];
        $activityCount = (clone $activityScope)->count();
        $recentActivity = (clone $activityScope)
            ->latest()
            ->limit(12)
            ->get();

        $resolvedPerformanceQuery = Ticket::query()
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->selectRaw('users.name as name, users.role as role, COUNT(*) as aggregate')
            ->whereNotNull('tickets.assigned_to')
            ->whereIn('tickets.status', $completedStatuses)
            ->when(
                $resolvedRange['start'] && $resolvedRange['end'],
                function (Builder $query) use ($resolvedRange): void {
                    $query->where(function (Builder $nestedQuery) use ($resolvedRange): void {
                        $nestedQuery
                            ->whereBetween('tickets.resolved_at', [$resolvedRange['start'], $resolvedRange['end']])
                            ->orWhereBetween('tickets.closed_at', [$resolvedRange['start'], $resolvedRange['end']]);
                    });
                }
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'role' => UserRole::from($row->role)->label(),
                'resolved_count' => (int) $row->aggregate,
            ]);

        $averageResolutionHours = Ticket::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('resolved_at')->orWhereNotNull('closed_at');
            })
            ->when(
                $resolvedRange['start'] && $resolvedRange['end'],
                function (Builder $query) use ($resolvedRange): void {
                    $query->where(function (Builder $nestedQuery) use ($resolvedRange): void {
                        $nestedQuery
                            ->whereBetween('resolved_at', [$resolvedRange['start'], $resolvedRange['end']])
                            ->orWhereBetween('closed_at', [$resolvedRange['start'], $resolvedRange['end']]);
                    });
                }
            )
            ->get(['created_at', 'resolved_at', 'closed_at'])
            ->map(function (Ticket $ticket): float {
                $endAt = $ticket->closed_at ?? $ticket->resolved_at;

                return $endAt
                    ? $ticket->created_at->diffInMinutes($endAt) / 60
                    : 0;
            })
            ->filter(fn (float $hours): bool => $hours > 0)
            ->avg();

        return [
            'range' => $range,
            'rangeLabel' => $resolvedRange['label'],
            'rangeOptions' => self::rangeOptions(),
            'generatedAt' => now(),
            'ticketCount' => $ticketCount,
            'activityCount' => $activityCount,
            'openTickets' => (int) ($statusCounts[TicketStatus::Open->value] ?? 0),
            'inProgressTickets' => (int) ($statusCounts[TicketStatus::InProgress->value] ?? 0),
            'pendingTickets' => (int) ($statusCounts[TicketStatus::Pending->value] ?? 0),
            'resolvedTickets' => (int) ($statusCounts[TicketStatus::Resolved->value] ?? 0),
            'closedTickets' => (int) ($statusCounts[TicketStatus::Closed->value] ?? 0),
            'activeTickets' => collect([
                (int) ($statusCounts[TicketStatus::Open->value] ?? 0),
                (int) ($statusCounts[TicketStatus::InProgress->value] ?? 0),
                (int) ($statusCounts[TicketStatus::Pending->value] ?? 0),
            ])->sum(),
            'criticalTickets' => (int) ($priorityCounts[TicketPriority::Critical->value] ?? 0),
            'statusBreakdown' => $statusBreakdown,
            'priorityBreakdown' => $priorityBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
            'teamPerformance' => $resolvedPerformanceQuery,
            'recentActivity' => $recentActivity,
            'averageResolutionLabel' => $this->formatResolutionTime($averageResolutionHours),
        ];
    }

    /**
     * @return array{label:string,start:?CarbonImmutable,end:?CarbonImmutable}
     */
    private function resolveRange(string $range): array
    {
        $now = CarbonImmutable::now();

        return match ($range) {
            '7d' => [
                'label' => 'Last 7 Days',
                'start' => $now->subDays(6)->startOfDay(),
                'end' => $now->endOfDay(),
            ],
            'this_month' => [
                'label' => 'This Month',
                'start' => $now->startOfMonth(),
                'end' => $now->endOfMonth(),
            ],
            'all' => [
                'label' => 'All Time',
                'start' => null,
                'end' => null,
            ],
            default => [
                'label' => 'Last 30 Days',
                'start' => $now->subDays(29)->startOfDay(),
                'end' => $now->endOfDay(),
            ],
        };
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
}
