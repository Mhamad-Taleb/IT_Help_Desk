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

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_center(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $agent = User::factory()->create([
            'name' => 'IT Support Agent',
            'role' => UserRole::SupportAgent,
        ]);

        $category = Category::factory()->create([
            'name' => 'Network',
        ]);

        $ticket = Ticket::factory()->create([
            'category_id' => $category->id,
            'priority' => TicketPriority::High,
            'status' => TicketStatus::Resolved,
            'assigned_to' => $agent->id,
            'resolved_at' => now()->subHours(4),
            'closed_at' => null,
        ]);

        AuditLog::factory()->create([
            'user_id' => $agent->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.updated',
            'description' => 'The ticket moved into a resolved state.',
            'subject_type' => Ticket::class,
            'subject_id' => $ticket->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSeeText('Administrative Reports');
        $response->assertSeeText('Export PDF');
        $response->assertSeeText('Support Output');
    }

    public function test_non_admin_cannot_access_reports_center(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($employee)->get(route('admin.reports.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_export_report_as_pdf(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Critical,
        ]);

        AuditLog::factory()->create([
            'user_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'action' => 'ticket.created',
            'description' => 'Admin report export seed activity.',
            'subject_type' => Ticket::class,
            'subject_id' => $ticket->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.pdf', [
            'range' => '30d',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_admin_can_switch_report_ranges_with_json_response(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('admin.reports.index', ['range' => '7d']));

        $response->assertOk();
        $response->assertJsonStructure([
            'range',
            'range_label',
            'generated_at',
            'export_url',
            'page_url',
            'html',
        ]);
        $response->assertJsonPath('range', '7d');
    }
}
