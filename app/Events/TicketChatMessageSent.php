<?php

namespace App\Events;

use App\Models\TicketChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public TicketChatMessage $message,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket-chat.'.$this->message->ticket_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('user');

        return [
            'message' => [
                'id' => $this->message->id,
                'ticket_id' => $this->message->ticket_id,
                'user_id' => $this->message->user_id,
                'user_name' => $this->message->user->name,
                'role_label' => $this->message->user->role->label(),
                'initial' => strtoupper(substr($this->message->user->name, 0, 1)),
                'body' => $this->message->body,
                'created_at' => $this->message->created_at->format('d M Y, h:i A'),
                'time' => $this->message->created_at->format('h:i A'),
            ],
        ];
    }
}
