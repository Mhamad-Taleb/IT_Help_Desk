<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    protected $model = TicketAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'original_name' => 'report.pdf',
            'storage_path' => 'ticket-attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => 12000,
        ];
    }
}
