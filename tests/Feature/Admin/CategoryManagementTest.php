<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_category_management_screen(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertSeeText('Ticket Categories');
    }

    public function test_non_admin_users_can_not_view_category_management_screen(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($employee)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Security Access',
            'description' => 'Badge, account permission, and internal access issues.',
            'is_active' => '1',
            'sort_order' => 6,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'name' => 'Security Access',
            'sort_order' => 6,
        ]);
    }

    public function test_admin_can_update_a_category(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::factory()->create([
            'name' => 'Network',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.categories.update', $category), [
            'name' => 'Network & VPN',
            'description' => 'Connectivity, internet, and VPN support.',
            'is_active' => '1',
            'sort_order' => 2,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Network & VPN',
            'sort_order' => 2,
        ]);
    }

    public function test_category_with_tickets_can_not_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $category = Category::factory()->create();
        Ticket::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
}
