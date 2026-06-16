<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open => 'bg-cyan-100 text-cyan-700',
            self::InProgress => 'bg-amber-100 text-amber-700',
            self::Pending => 'bg-violet-100 text-violet-700',
            self::Resolved => 'bg-emerald-100 text-emerald-700',
            self::Closed => 'bg-slate-200 text-slate-700',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }
}
