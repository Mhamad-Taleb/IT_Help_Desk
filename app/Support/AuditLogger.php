<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function record(
        string $action,
        string $description,
        ?User $actor = null,
        ?Model $subject = null,
        ?Ticket $ticket = null,
        ?User $targetUser = null,
        array $properties = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'ticket_id' => $ticket?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
        ]);
    }
}
