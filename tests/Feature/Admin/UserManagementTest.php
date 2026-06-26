<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_user_management_screen(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSeeTextInOrder(['User', 'Role Management']);
    }

    public function test_non_admin_users_can_not_view_the_user_management_screen(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($employee)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_a_users_role(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'username' => $employee->username,
            'email' => $employee->email,
            'role' => UserRole::Manager->value,
        ]);

        $response->assertRedirect();
        $this->assertSame(UserRole::Manager, $employee->fresh()->role);
    }

    public function test_admin_can_create_a_user_with_credentials(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Agent',
            'username' => 'newagent',
            'email' => 'newagent@example.com',
            'role' => UserRole::SupportAgent->value,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'New Agent',
            'username' => 'newagent',
            'email' => 'newagent@example.com',
            'role' => UserRole::SupportAgent->value,
        ]);
    }

    public function test_admin_can_update_a_users_details(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => 'Updated User',
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'role' => UserRole::Manager->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'username' => 'updateduser',
            'email' => 'updated@example.com',
            'role' => UserRole::Manager->value,
        ]);
    }

    public function test_admin_can_delete_a_non_admin_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_last_admin_can_not_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_can_view_activity_for_a_non_admin_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Employee User',
        ]);

        $ticket = Ticket::factory()->create([
            'created_by' => $employee->id,
        ]);

        AuditLog::factory()->create([
            'user_id' => $employee->id,
            'target_user_id' => $employee->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.created',
            'description' => 'Employee activity log.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.activity', $employee));

        $response->assertOk();
        $response->assertSeeText('Employee User');
        $response->assertSeeText('Linked Tickets');
        $response->assertSeeText('Activity Stream');
        $response->assertSeeText('Employee activity log.');
    }

    public function test_admin_cannot_open_activity_page_for_admin_account(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.activity', $otherAdmin));

        $response->assertNotFound();
    }
}
