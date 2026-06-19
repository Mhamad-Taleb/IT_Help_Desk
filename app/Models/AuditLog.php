<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_user_id',
        'ticket_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_log_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function clearedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_log_clears')
            ->withPivot('cleared_at')
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::Manager => $query->whereNotNull('ticket_id'),
            UserRole::SupportAgent => $query->where(function (Builder $nestedQuery) use ($user): void {
                $nestedQuery
                    ->where('user_id', $user->id)
                    ->orWhere('target_user_id', $user->id)
                    ->orWhereIn('ticket_id', Ticket::query()->visibleTo($user)->select('id'));
            }),
            UserRole::Employee => $query->where(function (Builder $nestedQuery) use ($user): void {
                $nestedQuery
                    ->where('user_id', $user->id)
                    ->orWhere('target_user_id', $user->id)
                    ->orWhereIn('ticket_id', $user->submittedTickets()->select('id'));
            }),
        };
    }

    public function scopeNotificationEligible(Builder $query): Builder
    {
        return $query->whereNotIn('action', [
            'auth.login',
            'auth.logout',
        ]);
    }

    public function scopeNotificationVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->visibleTo($user)
            ->notificationEligible()
            ->whereDoesntHave('clearedByUsers', fn (Builder $clearedQuery) => $clearedQuery->where('users.id', $user->id));
    }
}
