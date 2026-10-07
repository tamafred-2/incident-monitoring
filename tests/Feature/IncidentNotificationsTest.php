<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\Subdivision;
use App\Models\User;
use App\Notifications\IncidentUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_report_is_visible_in_staff_bell_and_staff_resolution_reaches_guards(): void
    {
        Storage::fake('public');
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'street' => 'Palm Street', 'block' => '1', 'lot' => '2']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $otherGuard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        $outsider = User::factory()->create(['role' => 'staff', 'subdivision_id' => null]);
        $inactive = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey(), 'is_active' => false]);
        $this->actingAs($guard)->post(route('incidents.store'), [
            'house_id' => $house->getKey(), 'description' => 'Broken gate', 'category' => 'Security', 'location' => 'Palm Street',
            'incident_date' => now()->subHour()->toDateTimeString(), 'reported_at' => now()->toDateTimeString(), 'status' => 'Open',
        ])->assertSessionHasNoErrors();
        $incident = Incident::sole();
        $this->assertSame(1, $staff->notifications()->count());
        $this->assertSame(0, $guard->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());
        $this->assertSame(0, $inactive->notifications()->count());
        $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertSee('Open notifications')->assertSee('New Incident Reported');
        $this->getJson(route('incident-notifications.index'))->assertOk()->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.incident_id', $incident->getKey())
            ->assertJsonPath('notifications.0.title', 'New Incident Reported');

        $incident->update(['assigned_to' => $staff->getKey()]);
        $this->put(route('incidents.update', $incident->getKey()), [
            'status' => 'Under Investigation', 'investigation_photos' => [UploadedFile::fake()->create('before.jpg', 100, 'image/jpeg')],
        ])->assertSessionHasNoErrors();
        $guard->notifications()->delete();
        $otherGuard->notifications()->delete();
        $this->put(route('incidents.update', $incident->getKey()), [
            'status' => 'Resolved', 'proof_photos' => [UploadedFile::fake()->create('after.jpg', 100, 'image/jpeg')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $guard->notifications()->count());
        $this->assertSame(1, $otherGuard->notifications()->count());
        $this->actingAs($guard)->getJson(route('incident-notifications.index'))->assertOk()
            ->assertJsonPath('notifications.0.title', 'Incident Resolved')->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.detail_url', route('incidents.show', $incident->getKey()));
        $this->get(route('dashboard'))->assertOk()->assertSee('Incident Resolved');
    }

    public function test_read_and_clear_controls_affect_only_the_current_users_notifications(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $guard = User::factory()->create(['role' => 'security']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $incident = Incident::create(['subdivision_id' => $subdivision->getKey(), 'description' => 'Broken gate', 'incident_date' => now(), 'status' => 'Open', 'reported_by' => $guard->getKey()]);
        $staff->notify(new IncidentUpdatedNotification($incident, 'New Incident Reported', 'Guard reported a broken gate.'));
        $guard->notify(new IncidentUpdatedNotification($incident, 'Incident Resolved', 'Staff repaired the gate.'));
        $staffKey = $staff->notifications()->sole()->id;
        $guardKey = $guard->notifications()->sole()->id;
        $this->actingAs($staff)->postJson(route('incident-notifications.read-one'), ['key' => $guardKey])->assertNotFound();
        $this->postJson(route('incident-notifications.read-one'), ['key' => $staffKey])->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertNull($guard->notifications()->sole()->read_at);
        $this->postJson(route('incident-notifications.read-all'))->assertOk();
        $this->delete(route('incident-notifications.clear-all'))->assertNoContent();
        $this->getJson(route('incident-notifications.index'))->assertJsonCount(0, 'notifications');
        $this->assertSame(1, $guard->notifications()->count());
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson(route('incident-notifications.index'))->assertUnauthorized();
    }
}
