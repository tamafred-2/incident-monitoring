<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_detail_page_shows_full_information(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Oak Ridge',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '1',
            'lot' => '2',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'North gate camera stopped recording overnight.',
            'category' => 'Security',
            'location' => 'North gate',
            'incident_date' => now()->subHour(),
            'reported_at' => now()->subMinutes(30),
            'resolved_at' => now(),
            'status' => 'Open',
            'proof_photo_path' => 'uploads/incidents/example-one.jpg',
            'reported_by' => $reporter->user_id,
        ]);

        IncidentPhoto::create([
            'incident_id' => $incident->incident_id,
            'photo_path' => 'uploads/incidents/example-one.jpg',
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('incidents.show', $incident->incident_id));

        $response
            ->assertOk()
            ->assertSee($incident->report_id)
            ->assertSee('North gate camera stopped recording overnight.')
            ->assertSee('Date Reported')
            ->assertSee('Date Resolved')
            ->assertSee('uploads/incidents/example-one.jpg', false)
            ->assertSee('openPreview(', false);
    }

    public function test_incident_deletion_uses_soft_deletes(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Maple Heights',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '2',
            'lot' => '4',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $reporter->user_id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('incidents.destroy', $incident->incident_id), [
                'view' => 'active',
            ]);

        $response
            ->assertRedirect(route('incidents.index'))
            ->assertSessionHas('success', 'Incident archived successfully.');

        $this->assertSoftDeleted('incidents', [
            'incident_id' => $incident->incident_id,
        ]);
    }

    public function test_archived_incident_can_be_restored(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'River Park',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'investigator',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '6',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Under Investigation',
            'reported_by' => $reporter->user_id,
        ]);

        $incident->delete();

        $response = $this
            ->actingAs($admin)
            ->post(route('incidents.restore', $incident->incident_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('incidents.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'Incident restored successfully.');

        $this->assertDatabaseHas('incidents', [
            'incident_id' => $incident->incident_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_incident_can_be_force_deleted(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Cedar Grove',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '5',
            'lot' => '8',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'incident_date' => now(),
            'reported_at' => now(),
            'resolved_at' => now(),
            'status' => 'Resolved',
            'reported_by' => $reporter->user_id,
        ]);

        $incident->delete();

        $response = $this
            ->actingAs($admin)
            ->delete(route('incidents.force-delete', $incident->incident_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('incidents.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'Incident permanently deleted.');

        $this->assertDatabaseMissing('incidents', [
            'incident_id' => $incident->incident_id,
        ]);
    }

    public function test_resident_can_submit_complaint_and_only_see_own_incidents(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Harbor View',
            'status' => 'Active',
        ]);

        $residentRecord = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Ana Cruz',
            'resident_code' => 'RES-9001',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '2',
            'lot' => '9',
        ]);

        $residentUser = User::factory()->create([
            'role' => 'resident',
            'subdivision_id' => $subdivision->subdivision_id,
            'resident_id' => $residentRecord->resident_id,
        ]);

        $otherReporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Other resident issue',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $otherReporter->user_id,
        ]);

        $storeResponse = $this
            ->actingAs($residentUser)
            ->post(route('incidents.store'), [
                'description' => 'The pipe under the sink is leaking.',
                'house_id' => $house->house_id,
                'category' => 'Property Damage',
                'location' => 'Kitchen',
                'incident_date' => now()->format('Y-m-d H:i:s'),
                'proof_photos' => [UploadedFile::fake()->create('resident-proof.jpg', 128, 'image/jpeg')],
            ]);

        $storeResponse
            ->assertRedirect(route('incidents.index'))
            ->assertSessionHas('success', 'Incident reported successfully.');

        $createdIncident = Incident::query()
            ->where('reported_by', $residentUser->user_id)
            ->latest('incident_id')
            ->firstOrFail();

        $this->assertDatabaseHas('incidents', [
            'description' => 'The pipe under the sink is leaking.',
            'house_id' => $house->house_id,
            'reported_by' => $residentUser->user_id,
            'verified_resident_id' => $residentRecord->resident_id,
            'status' => 'Open',
        ]);

        $indexResponse = $this
            ->actingAs($residentUser)
            ->get(route('incidents.index'));

        $indexResponse
            ->assertOk()
            ->assertSee($createdIncident->report_id)
            ->assertDontSee('Other resident issue');
    }

    public function test_admin_can_search_incidents_from_index(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Silver Oaks',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
            'first_name' => 'Paula',
            'surname' => 'Reyes',
            'email' => 'paula@example.com',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '1',
            'lot' => '1',
        ]);

        $matchingIncident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'description' => 'Sensor needs calibration.',
            'house_id' => $house->house_id,
            'category' => 'Security',
            'location' => 'Main gate',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $reporter->user_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'description' => 'Electrical issue near the park.',
            'house_id' => $house->house_id,
            'category' => 'Safety',
            'location' => 'Central park',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Under Investigation',
            'reported_by' => $reporter->user_id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('incidents.index', ['q' => 'sensor']));

        $response
            ->assertOk()
            ->assertSee($matchingIncident->report_id)
            ->assertDontSee('Garden lights flickering')
            ->assertSee('value="sensor"', false);
    }

    public function test_incident_photo_uploads_are_stored_on_the_public_disk_and_served_through_the_app(): void
    {
        Storage::fake('public');

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Pine Crest',
            'status' => 'Active',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '3',
        ]);

        $photo = UploadedFile::fake()->create('incident-proof.jpg', 128, 'image/jpeg');

        $response = $this
            ->actingAs($reporter)
            ->post(route('incidents.store'), [
                'subdivision_id' => $subdivision->subdivision_id,
                'description' => 'A wooden panel is broken near the side entrance.',
                'house_id' => $house->house_id,
                'category' => 'Property Damage',
                'location' => 'Clubhouse side entrance',
                'incident_date' => now()->subMinutes(20)->format('Y-m-d H:i:s'),
                'reported_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'Open',
                'proof_photos' => [$photo],
            ]);

        $response
            ->assertRedirect(route('incidents.index'))
            ->assertSessionHas('success', 'Incident reported successfully.');

        $incident = Incident::query()->latest('incident_id')->firstOrFail();
        $incidentPhoto = IncidentPhoto::query()->where('incident_id', $incident->incident_id)->firstOrFail();

        Storage::disk('public')->assertExists($incidentPhoto->photo_path);
        $this->assertSame($incidentPhoto->photo_path, $incident->proof_photo_path);

        $this->actingAs($reporter)
            ->get(route('incidents.photos.show', ['path' => $incidentPhoto->photo_path]))
            ->assertOk();
    }

    public function test_full_editor_can_remove_existing_incident_proof_images_during_update(): void
    {
        Storage::fake('public');

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Sunset Hills',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '7',
            'lot' => '2',
        ]);

        $photoOnePath = 'uploads/incidents/proof-one.jpg';
        $photoTwoPath = 'uploads/incidents/proof-two.jpg';
        Storage::disk('public')->put($photoOnePath, 'photo-one');
        Storage::disk('public')->put($photoTwoPath, 'photo-two');

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Fence post is leaning toward the street.',
            'category' => 'Property Damage',
            'location' => 'South perimeter',
            'incident_date' => now()->subHour(),
            'reported_at' => now()->subMinutes(30),
            'status' => 'Open',
            'proof_photo_path' => $photoOnePath,
            'reported_by' => $reporter->user_id,
        ]);

        IncidentPhoto::create([
            'incident_id' => $incident->incident_id,
            'photo_path' => $photoOnePath,
            'sort_order' => 0,
        ]);
        IncidentPhoto::create([
            'incident_id' => $incident->incident_id,
            'photo_path' => $photoTwoPath,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('incidents.update', $incident->incident_id), [
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house->house_id,
                'description' => 'Fence post is leaning toward the street.',
                'category' => 'Property Damage',
                'location' => 'South perimeter',
                'incident_date' => now()->subHour()->format('Y-m-d H:i:s'),
                'reported_at' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
                'status' => 'Open',
                'remove_proof_photos' => [$photoOnePath],
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('success', 'Incident updated successfully.');

        Storage::disk('public')->assertMissing($photoOnePath);
        Storage::disk('public')->assertExists($photoTwoPath);

        $this->assertDatabaseMissing('incident_photos', [
            'incident_id' => $incident->incident_id,
            'photo_path' => $photoOnePath,
        ]);
        $this->assertDatabaseHas('incident_photos', [
            'incident_id' => $incident->incident_id,
            'photo_path' => $photoTwoPath,
            'sort_order' => 0,
        ]);

        $incident->refresh();
        $this->assertSame($photoTwoPath, $incident->proof_photo_path);
    }

    public function test_admin_can_assign_incident_to_staff(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Rose Park',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
        $reporter = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '4',
            'lot' => '4',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Garage gate stuck halfway open.',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $reporter->user_id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('incidents.update', $incident->incident_id), [
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house->house_id,
                'description' => 'Garage gate stuck halfway open.',
                'category' => '',
                'location' => '',
                'incident_date' => now()->format('Y-m-d H:i:s'),
                'reported_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'Open',
                'assigned_to' => $staff->user_id,
            ]);

        $response->assertRedirect(route('incidents.show', [
            'incidentId' => $incident->incident_id,
            'subdivision_id' => $subdivision->subdivision_id,
        ]));

        $this->assertDatabaseHas('incidents', [
            'incident_id' => $incident->incident_id,
            'assigned_to' => $staff->user_id,
            'status' => 'Under Investigation',
        ]);
    }

    public function test_assigned_staff_can_update_incident_status(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Elm Gardens',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
        $reporter = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '8',
            'lot' => '1',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Broken sidewalk tile near clubhouse.',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Under Investigation',
            'reported_by' => $reporter->user_id,
            'assigned_to' => $staff->user_id,
        ]);

        $response = $this
            ->actingAs($staff)
            ->put(route('incidents.update', $incident->incident_id), [
                'status' => 'Resolved',
                'resolved_at' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect(route('incidents.show', ['incidentId' => $incident->incident_id]));

        $this->assertDatabaseHas('incidents', [
            'incident_id' => $incident->incident_id,
            'status' => 'Resolved',
            'assigned_to' => $staff->user_id,
        ]);
    }

    public function test_incident_can_be_viewed_by_report_id_route(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Birch Square',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '6',
            'lot' => '6',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Perimeter light is flickering.',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $staff->user_id,
        ]);

        $this->actingAs($staff)
            ->get(route('incidents.show-by-report', $incident->report_id))
            ->assertOk()
            ->assertSee($incident->report_id)
            ->assertSee('Perimeter light is flickering.');
    }

    public function test_security_cannot_access_incident_edit_routes(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Willow Heights',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '1',
            'lot' => '7',
        ]);

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'description' => 'Loose wiring near the gym corridor.',
            'category' => 'Safety',
            'location' => 'Gym corridor',
            'incident_date' => now()->subMinutes(25),
            'reported_at' => now()->subMinutes(10),
            'status' => 'Open',
            'reported_by' => $reporter->user_id,
        ]);

        $this->actingAs($security)
            ->get(route('incidents.edit', ['incidentId' => $incident->incident_id]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that page.');

        $this->actingAs($security)
            ->put(route('incidents.update', ['incidentId' => $incident->incident_id]), [
                'status' => 'Resolved',
                'resolved_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'You do not have permission to access that page.');
    }
}
