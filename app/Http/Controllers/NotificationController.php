<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, AuditLog $auditLog): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        abort_unless(
            AuditLog::query()
                ->notificationVisibleTo($user)
                ->whereKey($auditLog->id)
                ->exists(),
            403
        );

        DB::table('audit_log_reads')->updateOrInsert(
            [
                'audit_log_id' => $auditLog->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notification_id' => $auditLog->id,
            ]);
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $notificationIds = AuditLog::query()
            ->notificationVisibleTo($user)
            ->whereDoesntHave('readByUsers', fn ($query) => $query->where('users.id', $user->id))
            ->pluck('id');

        foreach ($notificationIds as $notificationId) {
            DB::table('audit_log_reads')->updateOrInsert(
                [
                    'audit_log_id' => $notificationId,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'marked_count' => $notificationIds->count(),
            ]);
        }

        return back();
    }

    public function clearAll(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $notificationIds = AuditLog::query()
            ->notificationVisibleTo($user)
            ->pluck('id');

        foreach ($notificationIds as $notificationId) {
            DB::table('audit_log_clears')->updateOrInsert(
                [
                    'audit_log_id' => $notificationId,
                    'user_id' => $user->id,
                ],
                [
                    'cleared_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'cleared_count' => $notificationIds->count(),
            ]);
        }

        return back();
    }
}
