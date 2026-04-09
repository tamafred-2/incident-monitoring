<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
