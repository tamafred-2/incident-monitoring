<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_detail_page_can_be_viewed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'subdivision_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('users.show', $targetUser))
            ->assertOk()
            ->assertSee('User Details')
            ->assertSee($targetUser->full_name);
    }

    public function test_user_can_be_deactivated_without_deletion(): void
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
            ->patch(route('users.status', $targetUser), ['is_active' => false]);

        $response
            ->assertRedirectContains(route('users.index'))
            ->assertSessionHas('success', 'User deactivated successfully.');

        $this->assertFalse($targetUser->fresh()->is_active);
        $this->assertNull($targetUser->fresh()->deleted_at);
        $this->patch(route('users.status', $targetUser), ['is_active' => true])->assertSessionHas('success', 'User activated successfully.');
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    public function test_user_archive_features_are_unavailable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'staff']);
        $targetUser->delete();

        $this->actingAs($admin)->get(route('users.index', ['view' => 'deleted']))
            ->assertOk()
            ->assertDontSee('Archived')
            ->assertDontSee($targetUser->email)
            ->assertViewHas('filterView', 'all');
        $this->post('/users/'.$targetUser->getKey().'/restore')->assertNotFound();
        $this->assertSoftDeleted($targetUser);
    }

    public function test_user_details_update_cannot_set_a_manual_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'admin']);
        $originalPassword = $targetUser->password;

        $this->actingAs($admin)->get(route('users.index'))->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);
        $this->put(route('users.update', $targetUser), [
            'surname' => 'Cruz', 'first_name' => 'Ana',
            'email' => $targetUser->email, 'role' => 'admin',
            'password' => 'manual-password', 'password_confirmation' => 'manual-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

        $this->assertSame($originalPassword, $targetUser->fresh()->password);
        $this->assertSame('Ana', $targetUser->fresh()->first_name);
    }

    public function test_user_deletion_endpoints_are_unavailable(): void
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

        $this->actingAs($admin)->delete('/users/'.$targetUser->getKey())->assertStatus(405);
        $this->delete('/users/'.$targetUser->getKey().'/force')->assertNotFound();
        $this->assertNotNull(User::withTrashed()->find($targetUser->getKey()));
    }
}
