<?php

namespace Tests\Feature;

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

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Broken gate camera',
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
            ->assertSee('Broken gate camera')
            ->assertSee('North gate camera stopped recording overnight.')
            ->assertSee('Date Reported')
            ->assertSee('Date Resolved')
            ->assertSee('uploads/incidents/example-one.jpg', false)
            ->assertSee('openPreview(', false)
            ->assertSee('nextPreview()', false);
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

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Unauthorized parking',
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

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Lobby glass damage',
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

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Basement leak',
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
            'title' => 'Other resident issue',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $otherReporter->user_id,
        ]);

        $storeResponse = $this
            ->actingAs($residentUser)
            ->post(route('incidents.store'), [
                'title' => 'Water leak in kitchen',
                'description' => 'The pipe under the sink is leaking.',
                'category' => 'Property Damage',
                'location' => 'Kitchen',
                'incident_date' => now()->format('Y-m-d H:i:s'),
            ]);

        $storeResponse
            ->assertRedirect(route('incidents.index'))
            ->assertSessionHas('success', 'Incident reported successfully.');

        $this->assertDatabaseHas('incidents', [
            'title' => 'Water leak in kitchen',
            'reported_by' => $residentUser->user_id,
            'verified_resident_id' => $residentRecord->resident_id,
            'status' => 'Open',
        ]);

        $indexResponse = $this
            ->actingAs($residentUser)
            ->get(route('incidents.index'));

        $indexResponse
            ->assertOk()
            ->assertSee('Water leak in kitchen')
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

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Gate sensor offline',
            'description' => 'Sensor needs calibration.',
            'category' => 'Security',
            'location' => 'Main gate',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $reporter->user_id,
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Garden lights flickering',
            'description' => 'Electrical issue near the park.',
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
            ->assertSee('Gate sensor offline')
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

        $photo = UploadedFile::fake()->create('incident-proof.jpg', 128, 'image/jpeg');

        $response = $this
            ->actingAs($reporter)
            ->post(route('incidents.store'), [
                'subdivision_id' => $subdivision->subdivision_id,
                'title' => 'Fence damage near clubhouse',
                'description' => 'A wooden panel is broken near the side entrance.',
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

    public function test_admin_can_remove_existing_incident_proof_photo_when_updating(): void
    {
        Storage::fake('public');

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Willow Creek',
            'status' => 'Active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $reporter = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $firstPath = 'uploads/incidents/willow-one.jpg';
        $secondPath = 'uploads/incidents/willow-two.jpg';

        Storage::disk('public')->put($firstPath, 'first-image');
        Storage::disk('public')->put($secondPath, 'second-image');

        $incident = Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'title' => 'Loose ceiling panel',
            'description' => 'Panel is hanging near the lobby lights.',
            'category' => 'Safety',
            'location' => 'Lobby',
            'incident_date' => now()->subHours(2),
            'reported_at' => now()->subHour(),
            'status' => 'Open',
            'proof_photo_path' => $firstPath,
            'reported_by' => $reporter->user_id,
        ]);

        IncidentPhoto::create([
            'incident_id' => $incident->incident_id,
            'photo_path' => $firstPath,
            'sort_order' => 0,
        ]);

        IncidentPhoto::create([
            'incident_id' => $incident->incident_id,
            'photo_path' => $secondPath,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('incidents.update', ['incidentId' => $incident->incident_id]), [
                'subdivision_id' => $subdivision->subdivision_id,
                'title' => 'Loose ceiling panel',
                'description' => 'Panel is hanging near the lobby lights.',
                'category' => 'Safety',
                'location' => 'Lobby',
                'incident_date' => now()->subHours(2)->format('Y-m-d H:i:s'),
                'reported_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'status' => 'Open',
                'remove_photo_paths' => [$firstPath],
            ]);

        $response
            ->assertRedirect(route('incidents.show', [
                'incidentId' => $incident->incident_id,
                'subdivision_id' => $subdivision->subdivision_id,
            ]))
            ->assertSessionHas('success', 'Incident updated successfully.');

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->assertDatabaseMissing('incident_photos', [
            'incident_id' => $incident->incident_id,
            'photo_path' => $firstPath,
        ]);

        $this->assertDatabaseHas('incident_photos', [
            'incident_id' => $incident->incident_id,
            'photo_path' => $secondPath,
            'sort_order' => 0,
        ]);

        $this->assertSame($secondPath, $incident->fresh()->proof_photo_path);
    }
}
