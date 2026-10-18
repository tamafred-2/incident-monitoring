<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\PasswordResetRequest;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordScrollLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_lists_return_only_the_next_filtered_batch_and_its_dialogs(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Scroll Home', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin', 'subdivision_id' => $home->subdivision_id]);
        for ($i = 1; $i <= 13; $i++) {
            Resident::create(['subdivision_id' => $home->subdivision_id, 'full_name' => sprintf('Scroll Resident %02d', $i), 'relation_to_owner' => 'Owner', 'status' => 'Active']);
            User::factory()->create(['role' => 'staff', 'subdivision_id' => $home->subdivision_id, 'first_name' => 'ScrollStaff', 'surname' => sprintf('%02d', $i), 'middle_name' => null, 'extension' => null]);
            House::create(['subdivision_id' => $home->subdivision_id, 'street' => 'Scroll Street', 'block' => (string) $i, 'lot' => '1']);
            Incident::create(['subdivision_id' => $home->subdivision_id, 'description' => "Scroll Incident {$i}", 'location' => "Scroll Location {$i}", 'status' => 'Open', 'incident_date' => now()->subMinutes($i), 'reported_at' => now()->subMinutes($i), 'reported_by' => $admin->user_id]);
            Visitor::create(['subdivision_id' => $home->subdivision_id, 'first_name' => "Scroll Visitor {$i}", 'surname' => 'Test', 'status' => 'Inside', 'check_in' => now()->subMinutes($i)]);
        }
        Resident::create(['subdivision_id' => $home->subdivision_id, 'full_name' => 'Unrelated Resident', 'status' => 'Active']);
        $this->actingAs($admin);

        $cases = [
            ['residents.index', ['q' => 'Scroll Resident', 'per_page' => 100], 'residents', 'residents', true],
            ['users.index', ['q' => 'ScrollStaff', 'role' => 'staff'], 'users', 'users', true],
            ['houses.index', ['q' => 'Scroll Street'], 'houses', 'houses', true],
            ['visitor-profiles.index', ['q' => 'Scroll Visitor'], 'visitor-profiles', 'profiles', false],
            ['subdivisions.show', ['subdivision' => $home, 'q' => 'Scroll Street'], 'subdivision-houses', 'houses', true],
            ['incidents.index', ['q' => 'Scroll Incident', 'reported_sort' => 'asc'], 'incidents', 'incidents', false],
            ['visitors.index', ['q' => 'Scroll Visitor', 'tab' => 'check-out'], 'visitor-inside', 'insideVisitors', false],
            ['visitors.index', ['q' => 'Scroll Visitor', 'tab' => 'history', 'chart_filter' => 1], 'visitor-history', 'visitors', false],
        ];

        foreach ($cases as [$route, $parameters, $list, $variable, $hasModals]) {
            $page = $this->get(route($route, $parameters))->assertOk();
            $records = $page->viewData($variable);
            $this->assertCount(10, $records, $list);
            $page->assertSee('data-scroll-list="'.$list.'"', false);
            $next = $this->getJson($records->nextPageUrl(), ['X-Scroll-List' => $list])
                ->assertOk()->assertJsonPath('count', 3)->assertJsonPath('next_url', null);
            $this->assertStringNotContainsString('<html', $next->json('html'));
            preg_match_all('/data-record-key="(\d+)"/', $next->json('html'), $matches);
            $this->assertCount(3, $matches[1], $list);
            $this->assertEmpty(array_intersect($records->modelKeys(), array_map('intval', $matches[1])), $list);
            $this->assertSame($hasModals, $next->json('modals') !== '', $list);
        }
    }

    public function test_scroll_requests_keep_subdivision_restrictions_and_reject_unknown_lists(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Home', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'Other', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->subdivision_id]);
        for ($i = 1; $i <= 12; $i++) {
            Resident::create(['subdivision_id' => $home->subdivision_id, 'full_name' => "Resident {$i}", 'status' => 'Active']);
        }
        Resident::create(['subdivision_id' => $other->subdivision_id, 'full_name' => 'Foreign Resident', 'status' => 'Active']);
        $this->actingAs($security)->getJson(route('residents.index', ['page' => 2, 'subdivision_id' => $other->subdivision_id]), ['X-Scroll-List' => 'residents'])
            ->assertOk()->assertJsonPath('count', 2)->assertDontSee('Foreign Resident');
        $this->getJson(route('residents.index'), ['X-Scroll-List' => 'users'])->assertNotFound();
        $this->getJson(route('users.index'), ['X-Scroll-List' => 'users'])->assertRedirect(route('dashboard'));
    }

    public function test_analytics_and_password_reset_lists_load_in_batches(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Card Lists', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin', 'subdivision_id' => $home->subdivision_id]);
        $resident = Resident::create(['subdivision_id' => $home->subdivision_id, 'full_name' => 'Request Owner', 'status' => 'Active', 'relation_to_owner' => 'Owner']);
        for ($i = 1; $i <= 23; $i++) {
            Resident::create(['subdivision_id' => $home->subdivision_id, 'full_name' => "Card Resident {$i}", 'status' => 'Active', 'relation_to_owner' => 'Owner']);
            if ($i <= 13) {
                PasswordResetRequest::create(['user_id' => $admin->user_id, 'email' => "reset{$i}@example.test", 'status' => 'completed', 'resolved_at' => now()]);
                PasswordResetRequest::create(['user_id' => $admin->user_id, 'email' => "pending{$i}@example.test", 'status' => 'pending', 'expires_at' => now()->addHour()]);
                VisitorRequest::create(['resident_id' => $resident->resident_id, 'subdivision_id' => $home->subdivision_id, 'visitor_name' => "Request {$i}", 'status' => 'Pending', 'requested_at' => now()->subMinutes($i)]);
            }
        }
        $this->actingAs($admin)->getJson(route('analytics.records', ['chart' => 'residentsRelation', 'value' => 'Owner', 'from' => now()->toDateString(), 'to' => now()->toDateString(), 'page' => 2]), ['X-Scroll-List' => 'analytics-records'])
            ->assertOk()->assertJsonPath('count', 4)->assertJsonPath('next_url', null);
        $this->getJson(route('admin.password-resets.index', ['page' => 2]), ['X-Scroll-List' => 'reset-history'])
            ->assertOk()->assertJsonPath('count', 3)->assertJsonPath('next_url', null);
        $this->getJson(route('admin.password-resets.index', ['pending_page' => 2]), ['X-Scroll-List' => 'reset-pending'])
            ->assertOk()->assertJsonPath('count', 3)->assertJsonPath('next_url', null);
    }
}
