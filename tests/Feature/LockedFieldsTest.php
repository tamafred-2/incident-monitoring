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

class LockedFieldsTest extends TestCase
{
    use RefreshDatabase;

    private Subdivision $subdivision;

    private House $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->subdivision = Subdivision::create(['subdivision_name' => 'Pine Trails', 'status' => 'Active']);
        $this->house = House::create(['subdivision_id' => $this->subdivision->getKey(), 'street' => 'Pine', 'block' => '1', 'lot' => '2']);
    }

    public function test_incident_reported_date_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reportedAt = now()->subDays(2)->startOfMinute();
        $incident = Incident::create([
            'subdivision_id' => $this->subdivision->getKey(), 'house_id' => $this->house->getKey(),
            'description' => 'Broken streetlight.', 'category' => 'Streetlight Problem', 'location' => 'Pine Street',
            'incident_date' => $reportedAt, 'reported_at' => $reportedAt, 'status' => 'Open', 'reported_by' => $admin->user_id,
        ]);

        $this->actingAs($admin)->get(route('incidents.edit', $incident->incident_id))->assertOk()
            ->assertDontSee('name="reported_at"', false);
        $this->put(route('incidents.update', $incident->incident_id), [
            'subdivision_id' => $this->subdivision->getKey(), 'house_id' => $this->house->getKey(),
            'description' => 'Broken streetlight near the gate.', 'category' => 'Streetlight Problem', 'location' => 'Pine Street',
            'incident_date' => $reportedAt->format('Y-m-d H:i:s'), 'reported_at' => now()->format('Y-m-d H:i:s'), 'status' => 'Open',
        ])->assertSessionHasNoErrors();

        $incident->refresh();
        $this->assertSame('Broken streetlight near the gate.', $incident->description);
        $this->assertSame($reportedAt->toDateTimeString(), $incident->reported_at->toDateTimeString());
    }

    public function test_staff_see_report_details_locked(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $this->subdivision->getKey()]);
        $incident = Incident::create([
            'subdivision_id' => $this->subdivision->getKey(), 'house_id' => $this->house->getKey(),
            'description' => 'Flooded drain.', 'category' => 'Drainage / Flooding', 'location' => 'Pine Street',
            'incident_date' => now()->subDay(), 'reported_at' => now()->subDay(), 'status' => 'Open',
            'reported_by' => $staff->user_id, 'assigned_to' => $staff->user_id,
        ]);

        $this->actingAs($staff)->get(route('incidents.edit', $incident->incident_id))->assertOk()
            ->assertSee('<fieldset class="contents" disabled', false);
    }

    public function test_visitor_gate_times_and_status_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $checkIn = now()->subHours(3);
        $visitor = Visitor::create([
            'subdivision_id' => $this->subdivision->getKey(), 'surname' => 'Cruz', 'first_name' => 'Ana',
            'phone' => '09171234567', 'status' => 'Inside', 'check_in' => $checkIn,
        ]);

        $this->actingAs($admin)->put(route('visitors.update', $visitor), [
            'surname' => 'Cruz', 'first_name' => 'Anna', 'phone' => '09171234567',
            'check_in' => now()->format('Y-m-d H:i:s'), 'check_out' => now()->format('Y-m-d H:i:s'), 'status' => 'Checked Out',
        ])->assertSessionHasNoErrors();

        $visitor->refresh();
        $this->assertSame('Anna', $visitor->first_name);
        $this->assertSame('Inside', $visitor->status->value);
        $this->assertSame($checkIn->toDateTimeString(), $visitor->check_in->toDateTimeString());
        $this->assertNull($visitor->check_out);
    }

    public function test_user_login_email_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $this->subdivision->getKey(), 'email' => 'staff@example.com']);

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'surname' => 'Reyes', 'first_name' => 'Mia', 'email' => 'changed@example.com',
            'role' => 'staff', 'is_active' => 1, 'subdivision_id' => $this->subdivision->getKey(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('staff@example.com', $staff->fresh()->email);
        $this->assertSame('Mia', $staff->fresh()->first_name);
    }

    public function test_assigned_resident_house_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherHouse = House::create(['subdivision_id' => $this->subdivision->getKey(), 'street' => 'Pine', 'block' => '3', 'lot' => '4']);
        $resident = Resident::create([
            'subdivision_id' => $this->subdivision->getKey(), 'house_id' => $this->house->getKey(),
            'full_name' => 'Paolo Reyes', 'status' => 'Active',
        ]);

        $this->actingAs($admin)->put(route('residents.update', $resident), [
            'surname' => 'Reyes', 'first_name' => 'Paolo', 'phone' => '09171234567', 'email' => 'paolo@example.com',
            'subdivision_id' => $this->subdivision->getKey(), 'house_id' => $otherHouse->getKey(), 'status' => 'Active',
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->house->getKey(), (int) $resident->fresh()->house_id);
    }
}
