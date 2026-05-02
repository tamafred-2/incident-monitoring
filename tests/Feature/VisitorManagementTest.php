<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_deletion_uses_soft_deletes(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Maple Grove',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($security)
            ->delete(route('visitors.destroy', $visitor), [
                'tab' => 'history',
                'view' => 'active',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'active',
            ]))
            ->assertSessionHas('success', 'Visitor archived successfully.');

        $this->assertSoftDeleted('visitors', [
            'visitor_id' => $visitor->visitor_id,
        ]);
    }

    public function test_archived_visitor_can_be_restored(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Pine Hills',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Santos',
            'first_name' => 'Lia',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Checked Out',
        ]);

        $visitor->delete();

        $response = $this
            ->actingAs($security)
            ->post(route('visitors.restore', $visitor->visitor_id), [
                'tab' => 'history',
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'deleted',
            ]))
            ->assertSessionHas('success', 'Visitor restored successfully.');

        $this->assertDatabaseHas('visitors', [
            'visitor_id' => $visitor->visitor_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_visitor_can_be_force_deleted(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Cedar Park',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Reyes',
            'first_name' => 'Tom',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Checked Out',
        ]);

        $visitor->delete();

        $response = $this
            ->actingAs($security)
            ->delete(route('visitors.force-delete', $visitor->visitor_id), [
                'tab' => 'history',
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'deleted',
            ]))
            ->assertSessionHas('success', 'Visitor permanently deleted.');

        $this->assertDatabaseMissing('visitors', [
            'visitor_id' => $visitor->visitor_id,
        ]);
    }

    public function test_security_can_check_in_visitor_with_valid_house_unit(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '12',
        ]);

        $response = $this
            ->actingAs($security)
            ->post(route('visitors.store'), [
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Cruz',
                'first_name' => 'Ana',
                'house_address_or_unit' => 'Block 3 Lot 12',
                'host_employee' => 'Homeowner',
            ]);

        $response->assertRedirect(route('visitors.index', [
            'tab' => 'history',
            'view' => 'active',
        ]));

        $this->assertDatabaseHas('visitors', [
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'house_address_or_unit' => 'Block 3 Lot 12',
        ]);
    }

    public function test_security_cannot_check_in_visitor_with_invalid_house_unit(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Southridge',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $response = $this
            ->actingAs($security)
            ->from(route('visitors.index'))
            ->post(route('visitors.store'), [
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Rivera',
                'first_name' => 'Toni',
                'house_address_or_unit' => 'BLK 9 LOT 99',
            ]);

        $response
            ->assertRedirect(route('visitors.index'))
            ->assertSessionHasErrors('house_address_or_unit');

        $this->assertDatabaseMissing('visitors', [
            'surname' => 'Rivera',
            'first_name' => 'Toni',
        ]);
    }

    public function test_security_can_search_visitors_from_monitoring(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'company' => 'Alpha Services',
            'host_employee' => 'Mr. Santos',
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Reyes',
            'first_name' => 'Tom',
            'company' => 'Beta Logistics',
            'host_employee' => 'Ms. Dela Cruz',
            'check_in' => now(),
            'status' => 'Checked Out',
        ]);

        $response = $this
            ->actingAs($security)
            ->get(route('visitors.index', ['q' => 'Alpha']));

        $response
            ->assertOk()
            ->assertSee('Alpha Services')
            ->assertDontSee('Beta Logistics')
            ->assertSee('value="Alpha"', false);
    }

    public function test_staff_cannot_access_visitor_monitoring_pages(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Green Field',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Flores',
            'first_name' => 'Rina',
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $this->actingAs($staff)
            ->get(route('visitors.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that page.');

        $this->actingAs($staff)
            ->get(route('visitors.show', ['visitor' => $visitor->visitor_id]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that page.');
    }
}
