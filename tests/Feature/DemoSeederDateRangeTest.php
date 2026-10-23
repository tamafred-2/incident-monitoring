<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeederDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_activity_covers_january_through_seed_time_in_order(): void
    {
        $this->travelTo(Carbon::parse('2026-09-12 14:30:00', config('app.timezone')));
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('houses', 124);
        $this->assertDatabaseCount('residents', 302);
        $this->assertDatabaseCount('incidents', 200);
        $this->assertDatabaseCount('house_ownership_transfers', 3);
        $this->assertDatabaseCount('expected_visits', 8);
        $this->assertSame(6, \App\Models\Resident::where('is_deceased', true)->count());
        $this->assertFalse(\App\Models\Resident::where('is_deceased', true)->where('status', 'Active')->exists());
        foreach (\App\Models\House::with('residents')->get() as $house) {
            $owners = $house->residents->where('relation_to_owner', 'Owner')->where('is_deceased', false);
            $this->assertGreaterThanOrEqual(1, $owners->count());
            $this->assertLessThanOrEqual(2, $owners->count());
            foreach ($house->residents as $resident) {
                $this->assertMatchesRegularExpression('/^[0-9]{11}$/', $resident->phone);
                $this->assertNotEmpty($resident->email);
                $this->assertSame($house->display_address, $resident->address_or_unit);
            }
        }
        $this->assertFalse(Incident::whereNull('investigation_description')->exists());
        $this->assertFalse(Incident::whereNotNull('resolved_at')->whereNull('resolution_description')->exists());
        $this->assertFalse(Visitor::whereNull('id_photo_path')->orWhereNull('visitor_profile_id')->exists());
        $this->assertSame([], \Illuminate\Support\Facades\DB::select('PRAGMA foreign_key_check'));

        foreach ([[Incident::class, 'reported_at'], [Visitor::class, 'check_in'], [VisitorRequest::class, 'requested_at']] as [$model, $column]) {
            $this->assertFalse($model::where($column, '<', '2026-01-01 00:00:00')->exists());
            $this->assertFalse($model::where($column, '>', now())->exists());
            $this->assertTrue($model::whereBetween($column, ['2026-01-01 00:00:00', '2026-01-07 23:59:59'])->exists());
            for ($month = 1; $month <= 9; $month++) {
                $this->assertTrue($model::whereMonth($column, $month)->exists(), "Missing month {$month} for {$model}");
            }
        }

        $this->assertFalse(Incident::whereColumn('reported_at', '<', 'incident_date')->exists());
        $this->assertFalse(Incident::whereColumn('resolved_at', '<', 'reported_at')->orWhere('resolved_at', '>', now())->exists());
        $this->assertFalse(Visitor::whereColumn('check_out', '<', 'check_in')->orWhere('check_out', '>', now())->exists());
        $this->assertFalse(VisitorRequest::whereColumn('responded_at', '<', 'requested_at')->orWhere('responded_at', '>', now())->exists());
    }
}
