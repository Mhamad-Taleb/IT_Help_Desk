<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $logs = AuditLog::query()
            ->with(['actor', 'targetUser', 'ticket'])
            ->visibleTo($user)
            ->latest()
            ->paginate(15);

        return view('history.index', [
            'actionCount' => AuditLog::query()->visibleTo($user)->count(),
            'logs' => $logs,
            'todayCount' => AuditLog::query()
                ->visibleTo($user)
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            'user' => $user,
        ]);
    }
}
