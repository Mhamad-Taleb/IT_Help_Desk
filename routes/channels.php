<?php

use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticket-chat.{ticketId}', function ($user, int $ticketId) {
    return Ticket::query()
        ->visibleTo($user)
        ->whereKey($ticketId)
        ->whereNotNull('assigned_to')
        ->exists();
});
