<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketChatMessage>
 */
class TicketChatMessageFactory extends Factory
{
    protected $model = TicketChatMessage::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
