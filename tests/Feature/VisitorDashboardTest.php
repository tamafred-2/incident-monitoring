<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_checked_in_widget_links_to_visitor_details(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'North Gate',
            'status' => 'Active',
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Dela Cruz',
            'first_name' => 'Maria',
            'middle_initials' => 'L.',
            'extension' => null,
            'phone' => '09123456789',
            'id_number' => 'ID-100',
            'company' => 'Sample Co',
            'purpose' => 'Meeting',
            'host_employee' => 'Reception',
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Visitors Currently Checked In')
            ->assertSee('North Gate')
            ->assertSee('Maria L. Dela Cruz')
            ->assertSee(route('visitors.show', ['visitor' => $visitor->visitor_id]), false);
    }

    public function test_authorized_user_can_view_visitor_details_page(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'South Gate',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Santos',
            'first_name' => 'Jose',
            'middle_initials' => 'P.',
            'extension' => 'Jr.',
            'phone' => '09999999999',
            'id_number' => 'VIS-22',
            'company' => 'Acme',
            'purpose' => 'Delivery',
            'host_employee' => 'Warehouse',
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($security)
            ->get(route('visitors.show', $visitor));

        $response
            ->assertOk()
            ->assertSee('Visitor Details')
            ->assertSee('Jose P. Santos Jr.')
            ->assertSee('Delivery');
    }

    public function test_dashboard_shows_house_and_resident_overview_counts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Central Park',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '1',
            'lot' => '6',
        ]);

        Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'full_name' => 'Mara Lopez',
            'resident_code' => 'RES-2001',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Total Residents')
            ->assertSee('Managed Houses')
            ->assertSee('Occupied Houses')
            ->assertSee('Central Park');
    }
}
