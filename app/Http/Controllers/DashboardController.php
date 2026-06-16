<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $ticketScope = Ticket::query()->visibleTo($user);

        return view('dashboard', [
            'categoryCount' => Category::query()->count(),
            'openTickets' => (clone $ticketScope)->where('status', TicketStatus::Open->value)->count(),
            'resolvedTickets' => (clone $ticketScope)->where('status', TicketStatus::Resolved->value)->count(),
            'ticketCount' => (clone $ticketScope)->count(),
            'user' => $user,
        ]);
    }
}
