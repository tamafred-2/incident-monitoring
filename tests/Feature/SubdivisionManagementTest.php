<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdivisionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_subdivision_detail_page_can_be_viewed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Sample Subdivision',
            'address' => 'Sample Address',
            'contact_person' => 'Sample Contact',
            'contact_number' => '1234567',
            'email' => 'sample@example.com',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('subdivisions.show', $subdivision))
            ->assertOk()
            ->assertSee('Subdivision Details')
            ->assertSee('Sample Subdivision');
    }

    public function test_subdivision_deletion_uses_soft_deletes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Sample Subdivision',
            'address' => 'Sample Address',
            'contact_person' => 'Sample Contact',
            'contact_number' => '1234567',
            'email' => 'sample@example.com',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('subdivisions.destroy', $subdivision));

        $response
            ->assertRedirect(route('subdivisions.index'))
            ->assertSessionHas('success', 'Subdivision archived successfully.');

        $this->assertSoftDeleted('subdivisions', [
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
    }

    public function test_archived_subdivision_can_be_restored(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Restore Subdivision',
            'address' => 'Sample Address',
            'contact_person' => 'Sample Contact',
            'contact_number' => '1234567',
            'email' => 'restore@example.com',
            'status' => 'Active',
        ]);

        $subdivision->delete();

        $response = $this
            ->actingAs($admin)
            ->post(route('subdivisions.restore', $subdivision->subdivision_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('subdivisions.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'Subdivision restored successfully.');

        $this->assertDatabaseHas('subdivisions', [
            'subdivision_id' => $subdivision->subdivision_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_subdivision_can_be_force_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Force Delete Subdivision',
            'address' => 'Sample Address',
            'contact_person' => 'Sample Contact',
            'contact_number' => '1234567',
            'email' => 'force@example.com',
            'status' => 'Active',
        ]);

        $subdivision->delete();

        $response = $this
            ->actingAs($admin)
            ->delete(route('subdivisions.force-delete', $subdivision->subdivision_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('subdivisions.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'Subdivision permanently deleted.');

        $this->assertDatabaseMissing('subdivisions', [
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
    }
}
