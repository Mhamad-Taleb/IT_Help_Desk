<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_visible_notification_as_read(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $notification = AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.updated',
            'description' => 'Visible notification entry.',
        ]);

        $response = $this->actingAs($employee)->patch(route('notifications.read', $notification));

        $response->assertRedirect();

        $this->assertDatabaseHas('audit_log_reads', [
            'audit_log_id' => $notification->id,
            'user_id' => $employee->id,
        ]);
    }

    public function test_employee_can_not_mark_hidden_notification_as_read(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $hiddenTicket = Ticket::factory()->create([
            'created_by' => $otherEmployee->id,
        ]);

        $hiddenNotification = AuditLog::factory()->create([
            'user_id' => $otherEmployee->id,
            'target_user_id' => $otherEmployee->id,
            'ticket_id' => $hiddenTicket->id,
            'action' => 'ticket.updated',
            'description' => 'Hidden notification entry.',
        ]);

        $response = $this->actingAs($employee)->patch(route('notifications.read', $hiddenNotification));

        $response->assertForbidden();

        $this->assertDatabaseMissing('audit_log_reads', [
            'audit_log_id' => $hiddenNotification->id,
            'user_id' => $employee->id,
        ]);
    }

    public function test_user_can_mark_all_visible_notifications_as_read(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $notifications = AuditLog::factory()->count(2)->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.updated',
        ]);

        $response = $this->actingAs($employee)->patch(route('notifications.read-all'), [], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();

        foreach ($notifications as $notification) {
            $this->assertDatabaseHas('audit_log_reads', [
                'audit_log_id' => $notification->id,
                'user_id' => $employee->id,
            ]);
        }
    }

    public function test_user_can_clear_all_visible_notifications_without_deleting_history(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $notification = AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.updated',
        ]);

        $response = $this->actingAs($employee)->patch(route('notifications.clear-all'), [], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('audit_log_clears', [
            'audit_log_id' => $notification->id,
            'user_id' => $employee->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $notification->id,
        ]);
    }
}
