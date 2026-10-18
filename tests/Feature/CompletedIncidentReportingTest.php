<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompletedIncidentReportingTest extends TestCase
{
    use RefreshDatabase;

    private function reportPayload(): array
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'street' => 'Palm Street', 'block' => '1', 'lot' => '2']);

        return [
            'subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(),
            'description' => 'Previously repaired gate.', 'category' => 'Security', 'location' => 'Palm Street, Block 1 Lot 2',
            'incident_date' => now()->subDays(3)->toDateTimeString(),
            'reported_at' => now()->subDays(3)->toDateTimeString(),
            'resolved_at' => now()->subDays(2)->toDateTimeString(), 'status' => 'Resolved',
        ];
    }

    public function test_completed_report_saves_historical_dates_and_goes_directly_to_history(): void
    {
        $this->freezeTime();
        Notification::fake();
        $payload = $this->reportPayload();
        $admin = User::factory()->create();
        User::factory()->create(['role' => 'staff', 'subdivision_id' => $payload['subdivision_id']]);
        $this->actingAs($admin)->get(route('incidents.index'))->assertOk()
            ->assertSee('id="report-status"', false)->assertSee('Date Resolved');
        $this->post(route('incidents.store'), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Completed incident saved to history.')->assertRedirectContains('view=history');
        $incident = Incident::sole();
        $this->assertSame('Resolved', $incident->status);
        $this->assertSame($payload['resolved_at'], $incident->resolved_at->toDateTimeString());
        $this->assertSame(now()->toDateTimeString(), $incident->reported_at->toDateTimeString());
        $this->assertNull($incident->assigned_to);
        $history = $this->get(route('incidents.index', ['view' => 'history']))->assertOk();
        $this->assertTrue($history->viewData('incidents')->contains('incident_id', $incident->getKey()));
        $active = $this->get(route('incidents.index'))->assertOk();
        $this->assertFalse($active->viewData('incidents')->contains('incident_id', $incident->getKey()));
        Notification::assertNothingSent();
    }

    public function test_staff_can_record_completed_history_with_optional_resolution_photos(): void
    {
        Storage::fake('public');
        $payload = $this->reportPayload();
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $payload['subdivision_id']]);
        $payload['proof_photos'] = [UploadedFile::fake()->create('completed.jpg', 128, 'image/jpeg')];
        $this->actingAs($staff)->post(route('incidents.store'), $payload)->assertSessionHasNoErrors()->assertRedirectContains('view=history');
        $photo = Incident::sole()->proofPhotos()->sole();
        $this->assertSame('resolution', $photo->stage);
        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_completion_date_is_required_and_must_be_between_incident_date_and_now(): void
    {
        $payload = $this->reportPayload();
        $this->actingAs(User::factory()->create());
        foreach ([null, now()->subDays(4)->toDateTimeString(), now()->addDay()->toDateTimeString()] as $date) {
            $payload['resolved_at'] = $date;
            $this->from(route('incidents.index'))->post(route('incidents.store'), $payload)->assertSessionHasErrors('resolved_at');
        }
        $this->get(route('incidents.index'))->assertOk()->assertSee('report-resolved-at')->assertSee('before or equal');
        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_staff_can_report_a_past_incident_without_supplying_a_reported_date(): void
    {
        $this->freezeTime();
        $payload = $this->reportPayload();
        unset($payload['reported_at']);
        $payload['status'] = 'Open';
        unset($payload['resolved_at']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $payload['subdivision_id']]);
        $this->actingAs($staff)->post(route('incidents.store'), $payload)->assertSessionHasNoErrors();
        $incident = Incident::sole();
        $this->assertSame(now()->toDateTimeString(), $incident->reported_at->toDateTimeString());
        $this->assertSame($payload['incident_date'], $incident->incident_date->toDateTimeString());
        $this->assertNull($incident->resolved_at);
    }

}
