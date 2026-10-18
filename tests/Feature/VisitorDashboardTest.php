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

    public function test_dashboard_checkout_returns_to_dashboard_and_removes_visitor(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'North Gate', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->subdivision_id]);
        $visitor = Visitor::create(['subdivision_id' => $subdivision->subdivision_id, 'first_name' => 'Maria', 'surname' => 'Cruz', 'check_in' => now(), 'status' => 'Inside']);
        $this->actingAs($security)->get(route('dashboard'))->assertOk()->assertSee('Check Out')->assertSee(route('visitors.checkout', $visitor), false);
        $this->post(route('visitors.checkout', $visitor), ['return_to' => 'dashboard'])
            ->assertRedirect(route('dashboard'))->assertSessionHas('success', 'Visitor checked out successfully.');
        $this->assertNotNull($visitor->fresh()->check_out);
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Maria Cruz');
    }

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
            ->assertSee('Visit Details')
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
            ->assertSee('Total Houses');
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
            ->assertSee('Total Incidents')
            ->assertSee('Total Residents')
            ->assertSee('Pending Incidents')
            ->assertSee('Total Houses')
            ->assertDontSee('Visitors Today')
            ->assertDontSee('Visitors Inside')
            ->assertDontSee('Visitors Currently Checked In');
    }

    public function test_staff_dashboard_pending_incident_count_excludes_resolved_and_closed(): void
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
            ->assertSee('Total Incidents')
            ->assertSee('Pending Incidents')
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
            ->assertSee('East gate')
            ->assertDontSee('Pool side');
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
            ->assertSee('Main entrance')
            ->assertViewHas('dashboardPendingIncidentList', fn ($records) => $records->pluck('location')->all() === ['Main entrance']);
    }

    public function test_dashboard_loads_five_visitors_and_scroll_cursor_survives_checkout(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Scroll Visitors', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->subdivision_id]);
        $visitors = collect();
        for ($i = 1; $i <= 12; $i++) {
            $visitors->push(Visitor::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'first_name' => "ScrollingVisitor{$i}", 'surname' => 'Test',
                'check_in' => now(), 'status' => 'Inside',
            ]));
        }

        $page = $this->actingAs($security)->get(route('dashboard', ['inside_per_page' => 100]));
        $page->assertOk()->assertSee('ScrollingVisitor12')->assertDontSee('ScrollingVisitor7');
        $initial = $page->viewData('insideVisitors');
        $this->assertCount(5, $initial);
        $this->assertSame($visitors->reverse()->take(5)->pluck('visitor_id')->values()->all(), $initial->pluck('visitor_id')->all());

        // Removing a previously loaded record must not shift or skip the next batch.
        $visitors->last()->update(['status' => 'Checked Out', 'check_out' => now()]);
        $next = $this->getJson(route('dashboard.records', [
            'table' => 'visitors', 'visitor_cursor' => $initial->nextCursor()->encode(),
        ]))->assertOk()->assertJsonPath('count', 5);
        $next->assertSee('ScrollingVisitor7')->assertDontSee('ScrollingVisitor12');
        $last = $this->getJson(route('dashboard.records', [
            'table' => 'visitors', 'visitor_cursor' => $next->json('next_cursor'),
        ]))->assertOk()->assertJsonPath('count', 2)->assertJsonPath('next_cursor', null);
        $last->assertSee('ScrollingVisitor1')->assertDontSee('ScrollingVisitor7');
    }

    public function test_scroll_endpoints_enforce_roles_subdivisions_and_active_statuses(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Home Scroll', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'Other Scroll', 'status' => 'Active']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $home->subdivision_id]);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->subdivision_id]);
        foreach ([$home, $other] as $subdivision) {
            foreach (['Open', 'Resolved'] as $status) {
                Incident::create([
                    'subdivision_id' => $subdivision->subdivision_id,
                    'description' => "{$subdivision->subdivision_name} {$status}",
                    'location' => "{$subdivision->subdivision_name} {$status}",
                    'status' => $status, 'incident_date' => now(), 'reported_by' => $staff->user_id,
                ]);
            }
            Visitor::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'first_name' => $subdivision->subdivision_name, 'surname' => 'Visitor', 'status' => 'Inside', 'check_in' => now(),
            ]);
        }

        $incidents = route('dashboard.records', ['table' => 'incidents']);
        $visitors = route('dashboard.records', ['table' => 'visitors']);
        $this->actingAs($staff)->getJson($incidents)->assertOk()->assertJsonPath('count', 1)
            ->assertSee('Home Scroll Open')->assertDontSee('Other Scroll')->assertDontSee('Home Scroll Resolved');
        $this->getJson($visitors)->assertForbidden();
        $this->actingAs($security)->getJson($visitors)->assertOk()->assertJsonPath('count', 1)
            ->assertSee('Home Scroll Visitor')->assertDontSee('Other Scroll');
        $this->getJson($incidents)->assertForbidden();
    }

    public function test_incident_scrolling_returns_separate_five_record_batches(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Incident Scroll', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin']);
        for ($i = 1; $i <= 11; $i++) {
            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'description' => "ScrollIncident{$i}", 'location' => "ScrollIncident{$i}",
                'status' => 'Under Investigation', 'incident_date' => now(), 'reported_by' => $admin->user_id,
            ]);
        }
        $page = $this->actingAs($admin)->get(route('dashboard'));
        $page->assertOk()->assertSee('ScrollIncident11');
        $initial = $page->viewData('dashboardPendingIncidentList');
        $this->assertCount(5, $initial);
        $this->assertSame(['ScrollIncident11', 'ScrollIncident10', 'ScrollIncident9', 'ScrollIncident8', 'ScrollIncident7'], $initial->pluck('location')->all());
        $next = $this->getJson(route('dashboard.records', [
            'table' => 'incidents', 'incident_cursor' => $initial->nextCursor()->encode(),
        ]))->assertOk()->assertJsonPath('count', 5);
        $next->assertSee('ScrollIncident6')->assertDontSee('ScrollIncident11');
        $this->getJson(route('dashboard.records', [
            'table' => 'incidents', 'incident_cursor' => $next->json('next_cursor'),
        ]))->assertOk()->assertJsonPath('count', 1)->assertJsonPath('next_cursor', null)
            ->assertSee('ScrollIncident1');
    }
    public function test_dashboard_searches_filter_the_full_lists_and_keep_scope_during_scrolling(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Search Home', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'Other', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $home->getKey()]);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        foreach ([$home, $other] as $subdivision) {
            for ($i = 1; $i <= 8; $i++) {
                $name = $i <= 6 ? 'Needle '.$i : 'Unrelated '.$i;
                Incident::create([
                    'subdivision_id' => $subdivision->getKey(), 'description' => $name,
                    'location' => $subdivision->subdivision_name.' '.$name, 'category' => 'Noise',
                    'status' => $i === 8 ? 'Resolved' : 'Open', 'incident_date' => now(), 'reported_by' => $admin->getKey(),
                ]);
                Visitor::create([
                    'subdivision_id' => $subdivision->getKey(), 'first_name' => $name, 'surname' => 'Visitor',
                    'host_employee' => $subdivision->subdivision_name,
                    'check_in' => now(), 'status' => $i === 8 ? 'Checked Out' : 'Inside',
                ]);
            }
        }
        $page = $this->actingAs($admin)->get(route('dashboard', ['incident_q' => 'Needle', 'visitor_q' => 'Unrelated']));
        $page->assertOk()->assertViewHas('dashboardPendingIncidentList', fn ($rows) => $rows->every(fn ($row) => str_contains($row->description, 'Needle')))
            ->assertViewHas('insideVisitors', fn ($rows) => $rows->count() === 2 && $rows->every(fn ($row) => str_contains($row->full_name, 'Unrelated')));
        foreach ([['incidents', 'incident_q', 'incident_cursor', $staff], ['visitors', 'visitor_q', 'visitor_cursor', $security]] as [$table, $key, $cursor, $user]) {
            $first = $this->actingAs($user)->getJson(route('dashboard.records', ['table' => $table, $key => 'Needle']))
                ->assertOk()->assertJsonPath('count', 5)->assertDontSee('Other')->assertDontSee('Unrelated');
            $this->getJson(route('dashboard.records', ['table' => $table, $key => 'Needle', $cursor => $first->json('next_cursor')]))
                ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('next_cursor', null)->assertSee('Needle 1')->assertDontSee('Other');
            $this->getJson(route('dashboard.records', ['table' => $table, $key => 'Missing']))
                ->assertOk()->assertJsonPath('count', 0)->assertJsonPath('next_cursor', null)->assertSee('match your search');
        }
    }

}
