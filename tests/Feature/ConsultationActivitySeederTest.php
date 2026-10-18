<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Database\Seeders\ConsultationActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationActivitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_data_covers_every_day_and_preserves_records_when_rerun(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'street' => 'Palm Street', 'block' => '1', 'lot' => '2']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        $resident = Resident::create([
            'full_name' => 'Ana Cruz', 'subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(), 'status' => 'Active',
        ]);
        $existing = Incident::create([
            'description' => 'Existing real report', 'subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(),
            'incident_date' => now(), 'reported_at' => now(), 'reported_by' => $guard->getKey(), 'status' => 'Open',
        ]);
        $existingData = $existing->fresh()->getAttributes();

        $obsolete = Incident::create([
            'description' => '[Consultation demo 2026-10-01] The entrance gate lock was repaired.',
            'subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(),
            'incident_date' => now(), 'reported_by' => $guard->getKey(), 'status' => 'Resolved',
        ]);
        $this->seed(ConsultationActivitySeeder::class);
        $incidentCounts = $visitorCounts = $requestCounts = [];
        foreach (range(1, 8) as $day) {
            $date = sprintf('2026-10-%02d', $day);
            $incidentCounts[] = Incident::where('description', 'like', '[Consultation demo%')->whereDate('reported_at', $date)->count();
            $visitorCounts[] = Visitor::whereDate('check_in', $date)->count();
            $requestCounts[] = VisitorRequest::whereDate('requested_at', $date)->count();
        }
        foreach ([$incidentCounts, $visitorCounts, $requestCounts] as $counts) {
            $this->assertGreaterThan(0, min($counts));
            $this->assertGreaterThan(2, count(array_unique($counts)));
            $this->assertGreaterThan(min($counts) * 2, max($counts));
        }
        $this->assertNull(Incident::withTrashed()->find($obsolete->getKey()));
        $this->assertGreaterThan(0, Incident::where('status', 'Resolved')->count());
        $this->assertGreaterThan(0, Visitor::where('status', 'Inside')->count());
        $this->assertGreaterThan(0, VisitorRequest::where('status', 'Pending')->count());
        $this->assertSame($resident->getKey(), VisitorRequest::first()->resident_id);
        $this->assertGreaterThan(1, Incident::where('description', 'like', '[Consultation demo%')->get()->pluck('reported_at')->map->format('i')->unique()->count());
        foreach (Incident::where('status', 'Resolved')->whereNotNull('resolved_at')->get() as $incident) {
            $this->assertTrue($incident->resolved_at->greaterThanOrEqualTo($incident->reported_at));
        }
        foreach (Visitor::whereNotNull('check_out')->get() as $visitor) {
            $this->assertTrue($visitor->check_out->greaterThan($visitor->check_in));
        }
        $snapshots = [
            Incident::orderBy('incident_id')->get()->toArray(),
            Visitor::orderBy('visitor_id')->get()->toArray(),
            VisitorRequest::orderBy('request_id')->get()->toArray(),
        ];

        $this->seed(ConsultationActivitySeeder::class);
        $this->assertSame($snapshots, [
            Incident::orderBy('incident_id')->get()->toArray(),
            Visitor::orderBy('visitor_id')->get()->toArray(),
            VisitorRequest::orderBy('request_id')->get()->toArray(),
        ]);
        $this->assertSame($existingData, $existing->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('residents', 1);
        $this->assertDatabaseCount('houses', 1);
    }
}
