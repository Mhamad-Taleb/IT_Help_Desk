<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'assigned_to' => $agent->id,
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

    public function test_support_agent_can_view_unassigned_ticket(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($agent)->get(route('tickets.show', $ticket));

        $response->assertOk();
    }

    public function test_support_agent_can_take_ownership_of_unassigned_ticket(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
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
            'assigned_to' => $agent->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $agent->id,
            'status' => TicketStatus::InProgress->value,
        ]);
    }

    public function test_comments_are_deleted_when_ticket_is_resolved(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::InProgress,
            'assigned_to' => $agent->id,
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
            'assigned_to' => $agent->id,
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

    public function test_employee_can_add_a_comment_with_async_submission(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)->post(
            route('tickets.messages.store', $ticket),
            [
                'body' => 'This is an async popup comment.',
            ],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertOk();
        $response->assertJson([
            'message' => 'Comment added successfully.',
        ]);
        $response->assertJsonPath('comment.body', 'This is an async popup comment.');

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'body' => 'This is an async popup comment.',
        ]);
    }

    public function test_employee_can_upload_an_allowed_file_to_their_ticket(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)->post(route('tickets.attachments.store', $ticket), [
            'attachments' => [
                UploadedFile::fake()->create('network-report.pdf', 200, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));

        $attachment = TicketAttachment::query()->first();

        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->storage_path);
        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'original_name' => 'network-report.pdf',
            'extension' => 'pdf',
        ]);
    }

    public function test_executable_file_upload_is_blocked_with_friendly_message(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)->from(route('tickets.show', $ticket))->post(route('tickets.attachments.store', $ticket), [
            'attachments' => [
                UploadedFile::fake()->create('installer.exe', 80, 'application/octet-stream'),
            ],
        ]);

        $response->assertRedirect(route('tickets.show', $ticket));
        $response->assertSessionHasErrors('attachments.0');
        $response->assertSessionHasErrors([
            'attachments.0' => 'Only PDF, TXT, DOCX, JPG, JPEG, and PNG files are allowed.',
        ]);

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_attachment_upload_validation_returns_json_for_async_popup_submission(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $response = $this->actingAs($employee)->post(
            route('tickets.attachments.store', $ticket),
            [],
            [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Please choose at least one file to upload.',
        ]);
    }

    public function test_authorized_user_can_open_ticket_attachment_inline(): void
    {
        Storage::fake('local');

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        $storedFile = UploadedFile::fake()->create('reference.pdf', 120, 'application/pdf');
        $storedPath = $storedFile->store("ticket-attachments/{$ticket->id}", 'local');

        $attachment = TicketAttachment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'original_name' => 'reference.pdf',
            'storage_path' => $storedPath,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'file_size' => $storedFile->getSize(),
        ]);

        $response = $this->actingAs($employee)->get(route('tickets.attachments.open', $attachment));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'inline; filename="reference.pdf"');
    }

    public function test_support_agent_can_add_a_comment_to_the_ticket(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $ticket = Ticket::factory()->create([
            'assigned_to' => $agent->id,
        ]);

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
            'assigned_to' => $agent->id,
        ]);

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'I have started troubleshooting and will update you soon.',
            'is_internal' => false,
        ]);

        $response = $this->actingAs($employee)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSee('I have started troubleshooting and will update you soon.', false);
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
            'assigned_to' => $agent->id,
        ]);

        TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'body' => 'Here is more information about the issue after testing.',
            'is_internal' => false,
        ]);

        $response = $this->actingAs($agent)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSee('Here is more information about the issue after testing.', false);
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
