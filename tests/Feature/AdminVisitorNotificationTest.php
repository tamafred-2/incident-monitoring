<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVisitorNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_visitor_activity_notification_panel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'West Ridge',
            'status' => 'Active',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Rivera',
            'first_name' => 'Ana',
            'middle_initials' => null,
            'extension' => null,
            'purpose' => 'Document drop-off',
            'host_employee' => 'Admin Office',
            'check_in' => now()->subMinutes(5),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Visitor Notifications')
            ->assertSee('Ana Rivera')
            ->assertSee('checked in at West Ridge.');
    }

    public function test_admin_notification_endpoint_returns_recent_visitor_activity(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'East Gate',
            'status' => 'Active',
        ]);

        $checkedInVisitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Santos',
            'first_name' => 'Leo',
            'middle_initials' => null,
            'extension' => null,
            'purpose' => 'Pickup',
            'host_employee' => 'Lobby',
            'check_in' => now()->subMinutes(8),
            'status' => 'Inside',
        ]);

        $checkedOutVisitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Garcia',
            'first_name' => 'Mia',
            'middle_initials' => null,
            'extension' => null,
            'purpose' => 'Delivery',
            'host_employee' => 'Warehouse',
            'check_in' => now()->subMinutes(30),
            'check_out' => now()->subMinute(),
            'status' => 'Checked Out',
        ]);

        $response = $this
            ->actingAs($admin)
            ->getJson(route('admin.visitor-notifications.index'));

        $response
            ->assertOk()
            ->assertJsonCount(3, 'notifications')
            ->assertJsonPath('unread_count', 3)
            ->assertJsonPath('notifications.0.visitor_name', $checkedOutVisitor->full_name)
            ->assertJsonPath('notifications.0.type', 'checked_out')
            ->assertJsonPath('notifications.1.visitor_name', $checkedInVisitor->full_name)
            ->assertJsonPath('notifications.1.type', 'checked_in');
    }

    public function test_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'North Hills',
            'status' => 'Active',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Torres',
            'first_name' => 'Paolo',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now()->subMinutes(2),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson(route('admin.visitor-notifications.read-all'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($admin->fresh()->visitor_notifications_read_at);
    }

    public function test_admin_can_mark_a_single_notification_as_read(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Lake View',
            'status' => 'Active',
        ]);

        $firstVisitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Lim',
            'first_name' => 'Ava',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now()->subMinutes(3),
            'status' => 'Inside',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Reyes',
            'first_name' => 'Noah',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now()->subMinute(),
            'status' => 'Inside',
        ]);

        $notificationKey = sprintf(
            '%s:%s:%s',
            $firstVisitor->visitor_id,
            'checked_in',
            $firstVisitor->check_in->timestamp
        );

        $response = $this
            ->actingAs($admin)
            ->postJson(route('admin.visitor-notifications.read-one'), [
                'key' => $notificationKey,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1);

        $this->assertContains($notificationKey, $admin->fresh()->visitor_notification_read_keys);
    }

    public function test_admin_can_clear_all_notifications_for_their_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Central Park',
            'status' => 'Active',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Flores',
            'first_name' => 'Nina',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now()->subMinutes(4),
            'status' => 'Inside',
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.visitor-notifications.clear-all'))
            ->assertNoContent();

        $admin->refresh();

        $this->assertNotNull($admin->visitor_notifications_read_at);
        $this->assertNotNull($admin->visitor_notifications_cleared_at);

        $this
            ->actingAs($admin)
            ->getJson(route('admin.visitor-notifications.index'))
            ->assertOk()
            ->assertJsonCount(0, 'notifications')
            ->assertJsonPath('unread_count', 0);
    }

    public function test_non_admin_cannot_access_admin_notification_endpoint(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('admin.visitor-notifications.index'));

        $response->assertRedirect(route('dashboard'));
    }
}
