<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Critical => 'Critical',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700',
            self::Medium => 'bg-sky-100 text-sky-700',
            self::High => 'bg-amber-100 text-amber-700',
            self::Critical => 'bg-rose-100 text-rose-700',
        };
    }
}
