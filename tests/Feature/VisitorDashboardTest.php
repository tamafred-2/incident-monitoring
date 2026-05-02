<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
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

    public function test_staff_dashboard_hides_visitor_monitoring_widgets(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'West Lake',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '2',
            'lot' => '3',
        ]);

        Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Diaz',
            'first_name' => 'Paolo',
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Active Incidents')
            ->assertSee('Total Residents')
            ->assertSee('Pending Incidents')
            ->assertSee('Total Houses')
            ->assertDontSee('Visitors Today')
            ->assertDontSee('Visitors Inside')
            ->assertDontSee('Visitors Currently Checked In');
    }

    public function test_staff_dashboard_active_incident_count_excludes_resolved_and_closed(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Lakewood',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '4',
            'lot' => '8',
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Open issue',
            'category' => 'Security',
            'location' => 'North gate',
            'incident_date' => now()->subMinutes(40),
            'reported_at' => now()->subMinutes(30),
            'status' => 'Open',
            'reported_by' => $staff->user_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Investigating issue',
            'category' => 'Safety',
            'location' => 'Clubhouse',
            'incident_date' => now()->subMinutes(35),
            'reported_at' => now()->subMinutes(25),
            'status' => 'Under Investigation',
            'reported_by' => $staff->user_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Resolved issue',
            'category' => 'Property Damage',
            'location' => 'Parking',
            'incident_date' => now()->subMinutes(20),
            'reported_at' => now()->subMinutes(15),
            'status' => 'Resolved',
            'reported_by' => $staff->user_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Closed issue',
            'category' => 'Noise Complaint',
            'location' => 'Community hall',
            'incident_date' => now()->subMinutes(10),
            'reported_at' => now()->subMinutes(5),
            'status' => 'Closed',
            'reported_by' => $staff->user_id,
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Active Incidents')
            ->assertSee('Pending Incidents')
            ->assertSeeInOrder(['Active Incidents', '2'])
            ->assertSeeInOrder(['Pending Incidents', '2']);
    }

    public function test_staff_dashboard_pending_incident_list_shows_only_active_cases(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Meadow Park',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '5',
            'lot' => '2',
        ]);

        Incident::create([
            'report_id' => 'PEND0001',
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Gate latch issue',
            'category' => 'Security',
            'location' => 'East gate',
            'incident_date' => now()->subHour(),
            'reported_at' => now()->subMinutes(50),
            'status' => 'Open',
            'reported_by' => $staff->user_id,
        ]);

        Incident::create([
            'report_id' => 'DONE0001',
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Issue resolved',
            'category' => 'Safety',
            'location' => 'Pool side',
            'incident_date' => now()->subMinutes(40),
            'reported_at' => now()->subMinutes(35),
            'status' => 'Resolved',
            'reported_by' => $staff->user_id,
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Pending Incidents')
            ->assertSee('PEND0001')
            ->assertDontSee('DONE0001');
    }

    public function test_admin_dashboard_pending_incident_list_shows_only_active_cases(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'North Ridge',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '7',
        ]);

        Incident::create([
            'report_id' => 'ADMP0001',
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Main entrance alarm issue',
            'category' => 'Security',
            'location' => 'Main entrance',
            'incident_date' => now()->subMinutes(25),
            'reported_at' => now()->subMinutes(20),
            'status' => 'Open',
            'reported_by' => $admin->user_id,
        ]);

        Incident::create([
            'report_id' => 'ADMC0001',
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Issue closed',
            'category' => 'Safety',
            'location' => 'Clubhouse',
            'incident_date' => now()->subMinutes(15),
            'reported_at' => now()->subMinutes(10),
            'status' => 'Closed',
            'reported_by' => $admin->user_id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Pending Incidents')
            ->assertSee('ADMP0001')
            ->assertDontSee('ADMC0001');
    }
}
