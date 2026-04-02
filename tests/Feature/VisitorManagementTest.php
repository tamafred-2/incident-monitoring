<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_deletion_uses_soft_deletes(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Maple Grove',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Inside',
        ]);

        $response = $this
            ->actingAs($security)
            ->delete(route('visitors.destroy', $visitor), [
                'tab' => 'history',
                'view' => 'active',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'active',
            ]))
            ->assertSessionHas('success', 'Visitor archived successfully.');

        $this->assertSoftDeleted('visitors', [
            'visitor_id' => $visitor->visitor_id,
        ]);
    }

    public function test_archived_visitor_can_be_restored(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Pine Hills',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Santos',
            'first_name' => 'Lia',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Checked Out',
        ]);

        $visitor->delete();

        $response = $this
            ->actingAs($security)
            ->post(route('visitors.restore', $visitor->visitor_id), [
                'tab' => 'history',
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'deleted',
            ]))
            ->assertSessionHas('success', 'Visitor restored successfully.');

        $this->assertDatabaseHas('visitors', [
            'visitor_id' => $visitor->visitor_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_visitor_can_be_force_deleted(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Cedar Park',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $visitor = Visitor::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Reyes',
            'first_name' => 'Tom',
            'middle_initials' => null,
            'extension' => null,
            'check_in' => now(),
            'status' => 'Checked Out',
        ]);

        $visitor->delete();

        $response = $this
            ->actingAs($security)
            ->delete(route('visitors.force-delete', $visitor->visitor_id), [
                'tab' => 'history',
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('visitors.index', [
                'tab' => 'history',
                'view' => 'deleted',
            ]))
            ->assertSessionHas('success', 'Visitor permanently deleted.');

        $this->assertDatabaseMissing('visitors', [
            'visitor_id' => $visitor->visitor_id,
        ]);
    }
}
