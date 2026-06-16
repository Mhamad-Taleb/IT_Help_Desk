<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_a_ticket(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);
        $category = Category::factory()->create();

        $response = $this->actingAs($employee)->post(route('tickets.store'), [
            'title' => 'Laptop cannot connect to Wi-Fi',
            'description' => 'The laptop sees the network but fails during authentication.',
            'category_id' => $category->id,
            'priority' => TicketPriority::High->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Laptop cannot connect to Wi-Fi',
            'created_by' => $employee->id,
            'category_id' => $category->id,
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::Open->value,
        ]);
    }

    public function test_admin_can_not_access_ticket_creation_screen(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get(route('tickets.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_not_create_a_ticket(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin)->post(route('tickets.store'), [
            'title' => 'Unauthorized admin ticket',
            'description' => 'This should not be allowed.',
            'category_id' => $category->id,
            'priority' => TicketPriority::Medium->value,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('tickets', [
            'title' => 'Unauthorized admin ticket',
            'created_by' => $admin->id,
        ]);
    }

    public function test_employee_only_sees_their_own_tickets(): void
    {
        $employee = User::factory()->create([
            'name' => 'Employee One',
            'role' => UserRole::Employee,
        ]);

        $otherEmployee = User::factory()->create([
            'name' => 'Employee Two',
            'role' => UserRole::Employee,
        ]);

        Ticket::factory()->create([
            'title' => 'Visible Ticket',
            'created_by' => $employee->id,
        ]);

        Ticket::factory()->create([
            'title' => 'Hidden Ticket',
            'created_by' => $otherEmployee->id,
        ]);

        $response = $this->actingAs($employee)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSeeText('Visible Ticket');
        $response->assertDontSeeText('Hidden Ticket');
    }

    public function test_support_agent_can_update_ticket_status_and_assignment(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $assignee = User::factory()->create([
            'role' => UserRole::Manager,
        ]);

        $ticket = Ticket::factory()->create([
            'assigned_to' => null,
            'status' => TicketStatus::Open,
        ]);

        $response = $this->actingAs($agent)->patch(route('tickets.update', $ticket), [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => $ticket->priority->value,
            'status' => TicketStatus::InProgress->value,
            'assigned_to' => $assignee->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::InProgress->value,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_comments_are_deleted_when_ticket_is_resolved(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::InProgress,
        ]);

        $comment = TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->actingAs($agent)->patch(route('tickets.update', $ticket), [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => $ticket->priority->value,
            'status' => TicketStatus::Resolved->value,
            'assigned_to' => $ticket->assigned_to,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('ticket_messages', [
            'id' => $comment->id,
        ]);
    }

    public function test_comments_are_deleted_when_ticket_is_closed(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Resolved,
        ]);

        $comment = TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->actingAs($agent)->patch(route('tickets.update', $ticket), [
            'title' => $ticket->title,
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => $ticket->priority->value,
            'status' => TicketStatus::Closed->value,
            'assigned_to' => $ticket->assigned_to,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('ticket_messages', [
            'id' => $comment->id,
        ]);
    }

    public function test_employee_can_add_a_comment_to_their_ticket(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)->post(route('tickets.messages.store', $ticket), [
            'body' => 'I restarted the laptop and the issue still exists.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'body' => 'I restarted the laptop and the issue still exists.',
            'is_internal' => false,
        ]);
    }

    public function test_support_agent_can_add_a_comment_to_the_ticket(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($agent)->post(route('tickets.messages.store', $ticket), [
            'body' => 'The issue has been checked and I am working on a fix.',
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'The issue has been checked and I am working on a fix.',
            'is_internal' => false,
        ]);
    }

    public function test_employee_can_see_support_comment_on_the_ticket_screen(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'I have started troubleshooting and will update you soon.',
            'is_internal' => false,
        ]);

        $response = $this->actingAs($employee)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSeeText('I have started troubleshooting and will update you soon.');
    }

    public function test_support_agent_can_see_employee_comment_on_the_ticket_screen(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'body' => 'Here is more information about the issue after testing.',
            'is_internal' => false,
        ]);

        $response = $this->actingAs($agent)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSeeText('Here is more information about the issue after testing.');
    }

    public function test_employee_can_not_update_another_users_ticket(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $otherEmployee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $otherEmployee->id,
            'status' => TicketStatus::Open,
        ]);

        $response = $this->actingAs($employee)->patch(route('tickets.update', $ticket), [
            'title' => 'Unauthorized Update',
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
            'priority' => TicketPriority::Low->value,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_a_ticket(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($admin)->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }
}
