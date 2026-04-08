<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentPhoto;
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
            ->assertSee('uploads/incidents/example-one.jpg', false);
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
}
