<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_user_id' => null,
            'ticket_id' => null,
            'action' => 'ticket.updated',
            'description' => fake()->sentence(),
            'subject_type' => Ticket::class,
            'subject_id' => null,
            'properties' => [],
        ];
    }
}
