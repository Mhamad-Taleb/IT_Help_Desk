<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case SupportAgent = 'support_agent';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::SupportAgent => 'IT Support Agent',
            self::Employee => 'Employee',
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $role) => $role->value,
            self::cases(),
        );
    }
}
