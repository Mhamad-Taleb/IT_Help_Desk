<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_history_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee One',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Employee One signed in.',
        ]);

        $response = $this->actingAs($employee)->get(route('history'));

        $response->assertOk();
        $response->assertSeeText('Historical Audit');
        $response->assertSeeText('Employee One signed in.');
    }

    public function test_employee_does_not_see_other_users_ticket_history(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Other Employee',
        ]);

        $otherTicket = Ticket::factory()->create([
            'created_by' => $otherEmployee->id,
        ]);

        AuditLog::factory()->create([
            'user_id' => $otherEmployee->id,
            'ticket_id' => $otherTicket->id,
            'action' => 'ticket.created',
            'description' => 'Other Employee created a hidden ticket.',
        ]);

        $response = $this->actingAs($employee)->get(route('history'));

        $response->assertOk();
        $response->assertDontSeeText('Other Employee created a hidden ticket.');
    }

    public function test_support_agent_sees_logs_for_visible_ticket_scope_and_own_activity(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee One',
        ]);

        $assignedTicket = Ticket::factory()->create([
            'created_by' => $employee->id,
            'assigned_to' => $agent->id,
        ]);

        $hiddenTicket = Ticket::factory()->create([
            'created_by' => $employee->id,
            'assigned_to' => null,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'ticket_id' => $assignedTicket->id,
            'action' => 'ticket.created',
            'description' => 'Assigned ticket activity visible to the agent.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'ticket_id' => $hiddenTicket->id,
            'action' => 'ticket.created',
            'description' => 'Unassigned ticket activity visible to the agent.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $agent->id,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Agent own login log remains visible.',
        ]);

        $response = $this->actingAs($agent)->get(route('history'));

        $response->assertOk();
        $response->assertSeeText('Assigned ticket activity visible to the agent.');
        $response->assertSeeText('Unassigned ticket activity visible to the agent.');
        $response->assertSeeText('Agent own login log remains visible.');
    }

    public function test_manager_sees_ticket_history_but_not_non_ticket_system_logs(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee One',
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.created',
            'description' => 'Manager visible ticket activity.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Manager hidden auth activity.',
        ]);

        $response = $this->actingAs($manager)->get(route('history'));

        $response->assertOk();
        $response->assertSeeText('Manager visible ticket activity.');
        $response->assertDontSeeText('Manager hidden auth activity.');
    }
}
