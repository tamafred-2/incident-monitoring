<?php

namespace Tests\Feature;

use App\Models\{House, Incident, Resident, Subdivision, User, Visitor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDrilldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_chart_opens_visitors_page_for_clicked_date_and_weekday(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Visitors', 'status' => 'Active']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->subdivision_id]);
        foreach (['2026-01-05 23:59:59', '2026-01-06 00:00:00', '2026-01-06 23:59:59', '2026-01-07 00:00:00'] as $index => $date) {
            Visitor::create(['subdivision_id' => $subdivision->subdivision_id, 'first_name' => 'Visitor', 'surname' => (string) $index, 'check_in' => $date, 'status' => $index === 1 ? 'Inside' : 'Checked Out']);
        }
        $this->actingAs($staff)->get(route('visitors.index', [
            'tab' => 'history', 'chart_filter' => 1, 'date_from' => '2026-01-06T00:00:00', 'date_to' => '2026-01-06T23:59:59',
        ]))->assertOk()->assertViewIs('visitors.index')
            ->assertViewHas('filterDateFrom', '2026-01-06 00:00:00')
            ->assertViewHas('visitors', fn ($rows) => $rows->total() === 2 && $rows->every(fn ($row) => $row->check_in->toDateString() === '2026-01-06'));
        $this->get(route('visitors.index', [
            'tab' => 'history', 'chart_filter' => 1, 'weekday' => 'Tue', 'date_from' => '2026-01-01T00:00:00', 'date_to' => '2026-01-31T23:59:59',
        ]))->assertOk()->assertViewHas('visitors', fn ($rows) => $rows->total() === 2);
        $this->post(route('visitors.store'), [])->assertRedirect(route('dashboard'));
    }

    public function test_clicked_date_excludes_other_days_and_includes_last_second(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Date test', 'status' => 'Active']);
        foreach (['2026-01-05 23:59:59', '2026-01-06 00:00:00', '2026-01-06 23:59:59', '2026-01-07 00:00:00'] as $date) {
            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'description' => $date,
                'incident_date' => $date,
                'reported_at' => $date,
                'reported_by' => $admin->user_id,
                'status' => 'Open',
            ]);
        }
        $this->actingAs($admin)->get(route('incidents.index', [
            'chart_filter' => 1, 'view' => 'all',
            'date_from' => '2026-01-06T00:00:00', 'date_to' => '2026-01-06T23:59:59',
        ]))->assertOk()
            ->assertViewHas('filterDateFrom', '2026-01-06 00:00:00')
            ->assertViewHas('filterDateTo', '2026-01-06 23:59:59')
            ->assertViewHas('incidents', fn ($rows) => $rows->total() === 2 && $rows->every(fn ($row) => $row->reported_at->toDateString() === '2026-01-06'));
    }

    public function test_chart_links_return_matching_records_and_preserve_scope(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Test', 'status' => 'Active']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->subdivision_id]);
        $house = House::create(['subdivision_id' => $subdivision->subdivision_id, 'block' => '1', 'lot' => '1']);
        $incident = Incident::create(['subdivision_id' => $subdivision->subdivision_id, 'house_id' => $house->house_id, 'description' => 'Matching incident', 'incident_date' => '2026-01-06 12:00:00', 'category' => 'Vandalism', 'status' => 'Open', 'reported_at' => '2026-01-06 12:00:00', 'reported_by' => $staff->user_id]);
        $visitor = Visitor::create(['subdivision_id' => $subdivision->subdivision_id, 'first_name' => 'Matching', 'surname' => 'Visitor', 'check_in' => '2026-01-06 12:00:00', 'status' => 'Inside']);
        $resident = Resident::create(['subdivision_id' => $subdivision->subdivision_id, 'full_name' => 'Matching owner', 'relation_to_owner' => 'Owner', 'status' => 'Active']);
        $this->actingAs($staff);
        $this->get(route('incidents.index', ['chart_filter' => 1, 'view' => 'all', 'status' => 'Pending']))
            ->assertOk()->assertViewHas('incidents', fn ($rows) => $rows->total() === 1);
        $incident->update(['status' => 'Under Investigation']);
        $this->get(route('incidents.index', ['chart_filter' => 1, 'view' => 'all', 'status' => 'Investigation']))
            ->assertOk()->assertViewHas('incidents', fn ($rows) => $rows->total() === 1)->assertSee('Investigation');
        $this->get(route('analytics.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()->assertViewHas('incidents', fn ($data) => $data['status_labels'] === ['Investigation']);
        $incident->update(['status' => 'Open']);
        $this->get(route('residents.index', ['relation_to_owner' => 'Owner']))
            ->assertOk()->assertViewIs('residents.index')
            ->assertViewHas('residents', fn ($rows) => $rows->total() === 1 && $rows->first()->resident_id === $resident->resident_id);
        $this->get(route('residents.index', ['relation_to_owner' => 'Son']))
            ->assertOk()->assertViewHas('residents', fn ($rows) => $rows->total() === 0);
        foreach ([[], ['category' => 'Vandalism'], ['status' => 'Open'], ['house_id' => $house->house_id]] as $filters) {
            $this->get(route('incidents.index', $filters + ['view' => 'all', 'chart_filter' => 1, 'date_from' => '2026-01-06T00:00:00', 'date_to' => '2026-01-06T23:59:59']))
                ->assertOk()->assertViewIs('incidents.index')->assertViewHas('incidents', fn ($rows) => $rows->total() === 1);
        }
        $this->get(route('incidents.index', ['view' => 'all', 'chart_filter' => 1, 'category' => 'Other']))
            ->assertOk()->assertViewHas('incidents', fn ($rows) => $rows->total() === 0);
        $incident->update(['status' => 'Resolved']);
        $this->get(route('incidents.index', ['view' => 'all', 'chart_filter' => 1, 'status' => 'Resolved']))
            ->assertOk()->assertViewHas('incidents', fn ($rows) => $rows->total() === 1);
        $incident->update(['status' => 'Open']);
        foreach ([
            ['incidentsTrend', '', $incident], ['incidentsCategory', 'Vandalism', $incident],
            ['incidentsStatus', 'Open', $incident], ['topHouses', (string) $house->house_id, $incident],
            ['visitorsTrend', '', $visitor], ['visitorsWeekday', 'Tue', $visitor],
            ['residentsRelation', 'Owner', $resident],
        ] as [$chart, $value, $record]) {
            $this->get(route('analytics.records', ['chart' => $chart, 'value' => $value, 'from' => '2026-01-01', 'to' => '2026-01-31']))
                ->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 1 && $rows->first()->getKey() === $record->getKey());
        }
        foreach ([['incidentsTrend', '', '2026-02-01', '2026-02-28'], ['visitorsWeekday', 'Mon', '2026-01-01', '2026-01-31'], ['incidentsCategory', 'Other', '2026-01-01', '2026-01-31']] as [$chart, $value, $from, $to]) {
            $this->get(route('analytics.records', compact('chart', 'value', 'from', 'to')))->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 0);
        }
        $resident->update(['status' => 'Inactive']);
        $this->get(route('analytics.records', ['chart' => 'residentsRelation', 'value' => 'Owner', 'from' => '2026-01-01', 'to' => '2026-01-31']))->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 0);
        $staff->update(['subdivision_id' => null]);
        $this->actingAs($staff->fresh())->get(route('analytics.records', ['chart' => 'incidentsTrend', 'from' => '2026-01-01', 'to' => '2026-01-31']))->assertOk()->assertViewHas('records', fn ($rows) => $rows->total() === 0);
    }

    public function test_weekly_chart_bucket_links_are_clipped_to_selected_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('analytics.index', ['from' => '2026-01-07', 'to' => '2026-01-13', 'granularity' => 'weekly']))
            ->assertOk()->assertViewHas('incidents', fn ($data) => $data['trend']['ranges'] === [
                ['from' => '2026-01-07', 'to' => '2026-01-11'],
                ['from' => '2026-01-12', 'to' => '2026-01-13'],
            ]);
    }
}
