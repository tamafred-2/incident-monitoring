<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_deletion_uses_soft_deletes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('users.destroy', $targetUser));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User archived successfully.');

        $this->assertSoftDeleted('users', [
            'user_id' => $targetUser->user_id,
        ]);
    }

    public function test_archived_user_can_be_restored(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $targetUser->delete();

        $response = $this
            ->actingAs($admin)
            ->post(route('users.restore', $targetUser->user_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('users.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'User restored successfully.');

        $this->assertDatabaseHas('users', [
            'user_id' => $targetUser->user_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_user_can_be_force_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $targetUser->delete();

        $response = $this
            ->actingAs($admin)
            ->delete(route('users.force-delete', $targetUser->user_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('users.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'User permanently deleted.');

        $this->assertDatabaseMissing('users', [
            'user_id' => $targetUser->user_id,
        ]);
    }
}
