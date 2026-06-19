<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_dashboard_only_shows_personal_ticket_analytics_and_notifications(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee User',
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Other Employee',
        ]);

        $category = Category::factory()->create();

        $ownOpenTicket = Ticket::factory()->create([
            'title' => 'Employee visible ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Open,
            'assigned_to' => null,
        ]);

        Ticket::factory()->create([
            'title' => 'Employee resolved ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Resolved,
            'assigned_to' => null,
        ]);

        $hiddenTicket = Ticket::factory()->create([
            'title' => 'Hidden team ticket',
            'category_id' => $category->id,
            'created_by' => $otherEmployee->id,
            'priority' => TicketPriority::Critical,
            'status' => TicketStatus::Open,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ownOpenTicket->id,
            'action' => 'ticket.updated',
            'description' => 'Visible employee notification.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $otherEmployee->id,
            'target_user_id' => $otherEmployee->id,
            'ticket_id' => $hiddenTicket->id,
            'action' => 'ticket.updated',
            'description' => 'Hidden employee notification.',
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('ticketCount', 2);
        $response->assertViewHas('urgentTicketCount', 1);
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->contains('Visible employee notification.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->doesntContain('Hidden employee notification.'));
        $response->assertDontSeeText('Top Categories');
        $response->assertDontSeeText('Priority Analytics');
        $response->assertDontSeeText('Tickets Created vs Resolved');
    }

    public function test_login_and_logout_activity_do_not_appear_in_dashboard_notifications(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.created',
            'description' => 'Visible ticket notification.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Hidden login notification.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => null,
            'action' => 'auth.logout',
            'description' => 'Hidden logout notification.',
        ]);

        $response = $this->actingAs($employee)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->contains('Visible ticket notification.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->doesntContain('Hidden login notification.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->doesntContain('Hidden logout notification.'));
    }

    public function test_support_dashboard_shows_visible_queue_analytics_and_ticket_notifications(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
            'name' => 'Support Agent',
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $category = Category::factory()->create();

        $unassignedTicket = Ticket::factory()->create([
            'title' => 'Unassigned high priority ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Open,
            'assigned_to' => null,
        ]);

        $assignedTicket = Ticket::factory()->create([
            'title' => 'Assigned support ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'priority' => TicketPriority::Medium,
            'status' => TicketStatus::InProgress,
            'assigned_to' => $agent->id,
        ]);

        Ticket::factory()->create([
            'title' => 'Resolved shared ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'priority' => TicketPriority::Low,
            'status' => TicketStatus::Resolved,
            'assigned_to' => $agent->id,
            'resolved_at' => now(),
        ]);

        AuditLog::factory()->create([
            'user_id' => $agent->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $assignedTicket->id,
            'action' => 'ticket.comment_added',
            'description' => 'Assigned support notification.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => null,
            'ticket_id' => $unassignedTicket->id,
            'action' => 'ticket.created',
            'description' => 'Employee opened a new unassigned ticket.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Hidden authentication notification.',
        ]);

        $response = $this->actingAs($agent)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('ticketCount', 3);
        $response->assertViewHas('unassignedTicketCount', 1);
        $response->assertViewHas('assignedToMeCount', 1);
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->contains('Assigned support notification.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->contains('Employee opened a new unassigned ticket.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->doesntContain('Hidden authentication notification.'));
        $response->assertDontSeeText('Top Categories');
        $response->assertDontSeeText('Priority Analytics');
        $response->assertDontSeeText('Tickets Created vs Resolved');
    }

    public function test_manager_dashboard_sees_ticket_activity_but_not_non_ticket_system_logs(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'name' => 'Support Manager',
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee One',
        ]);

        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
            'name' => 'Agent One',
        ]);

        $category = Category::factory()->create();

        $ticket = Ticket::factory()->create([
            'title' => 'Manager visible ticket',
            'category_id' => $category->id,
            'created_by' => $employee->id,
            'assigned_to' => $agent->id,
            'status' => TicketStatus::Open,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $agent->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.created',
            'description' => 'Employee One created the ticket for Agent One to take.',
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => null,
            'ticket_id' => null,
            'action' => 'auth.login',
            'description' => 'Hidden manager auth log.',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('ticketCount', 1);
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->contains('Employee One created the ticket for Agent One to take.'));
        $response->assertViewHas('recentNotifications', fn ($notifications) => $notifications
            ->pluck('description')
            ->doesntContain('Hidden manager auth log.'));
        $response->assertSeeText('Recent Ticket Movement');
        $response->assertDontSeeText('Top Categories');
        $response->assertDontSeeText('Priority Analytics');
    }
}
