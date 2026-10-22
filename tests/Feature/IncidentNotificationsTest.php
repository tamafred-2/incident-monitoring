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

    public function test_staff_report_names_its_creator_in_admin_and_security_notifications(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'street' => 'Palm Street', 'block' => '1', 'lot' => '2']);
        $staff = User::factory()->create(['first_name' => 'Ralph', 'surname' => 'Cruz', 'middle_name' => null, 'extension' => null, 'role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->post(route('incidents.store'), [
            'house_id' => $house->getKey(), 'description' => 'Broken gate', 'category' => 'Security', 'location' => 'Palm Street',
            'incident_date' => now()->subHour()->toDateTimeString(), 'status' => 'Open',
            'reported_by' => $guard->getKey(),
        ])->assertSessionHasNoErrors();

        $this->assertSame($staff->getKey(), Incident::sole()->reported_by);
        foreach ([[$admin, 'admin.visitor-notifications.index'], [$guard, 'incident-notifications.index']] as [$recipient, $route]) {
            $this->actingAs($recipient)->getJson(route($route))->assertOk()
                ->assertJsonPath('notifications.0.title', 'Ralph created a new incident')
                ->assertJsonPath('notifications.0.message', 'Security at Palm Street.');
        }

        $this->get(route('incidents.show', Incident::sole()->getKey()))->assertOk()
            ->assertSee('Created By')->assertSee('Ralph Cruz');

        $notification = $guard->notifications()->sole();
        $notification->update(['data' => array_merge($notification->data, ['title' => 'Ralph Cruz created a new incident'])]);
        $this->getJson(route('incident-notifications.index'))->assertOk()
            ->assertJsonPath('notifications.0.title', 'Ralph created a new incident');
    }

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
        $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertSee('Open notifications')->assertSee('created a new incident');
        $this->getJson(route('incident-notifications.index'))->assertOk()->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.incident_id', $incident->getKey())
            ->assertJsonPath('notifications.0.title', $guard->first_name.' created a new incident')
            ->assertJsonPath('notifications.0.message', 'Security at Palm Street.');

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
