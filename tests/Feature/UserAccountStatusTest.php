<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_uses_status_controls_and_filters_without_deletion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $active = User::factory()->create(['full_name' => 'Active Person', 'first_name' => 'Active', 'surname' => 'Person', 'middle_name' => null, 'extension' => null]);
        $inactive = User::factory()->create(['is_active' => false, 'first_name' => 'Inactive', 'surname' => 'Person', 'middle_name' => null, 'extension' => null]);
        $this->actingAs($admin)->get(route('users.index', ['view' => 'active']))->assertOk()
            ->assertSee('Active Person')->assertDontSee('Inactive Person')->assertSee('Deactivate')
            ->assertDontSee('Force Delete')->assertDontSee('Archive User');
        $this->get(route('users.index', ['view' => 'inactive']))->assertOk()
            ->assertSee('Inactive Person')->assertDontSee('Active Person')->assertSee('Activate')
            ->assertSee(route('users.status', $inactive), false);
    }

    public function test_deactivation_keeps_the_account_visible_in_the_default_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['first_name' => 'Ana', 'surname' => 'Cruz', 'middle_name' => null, 'extension' => null]);
        $this->actingAs($admin)->patch(route('users.status', $user), ['is_active' => false, 'view' => 'active'])
            ->assertRedirect(route('users.index', ['per_page' => 10, 'view' => 'all']));
        $response = $this->get(route('users.index'))->assertOk()->assertSee('Ana Cruz')->assertSee('Inactive')
            ->assertSee(route('users.status', $user), false);
        $this->assertTrue($response->viewData('users')->contains('user_id', $user->getKey()));
        $this->assertFalse($user->fresh()->is_active);
        $this->assertNull($user->fresh()->deleted_at);
    }

    public function test_only_admins_can_change_account_status_and_self_deactivation_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->patch(route('users.status', $admin), ['is_active' => false])->assertRedirect(route('dashboard'));
        $this->assertTrue($admin->fresh()->is_active);
        $this->actingAs($admin)->patch(route('users.status', $admin), ['is_active' => false])
            ->assertSessionHas('error', 'You cannot deactivate your own account.');
        $this->assertTrue($admin->fresh()->is_active);
        $this->patch(route('users.status', $staff), ['is_active' => 'invalid'])->assertSessionHasErrors('is_active');
        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_edit_cannot_remove_the_last_active_admin_or_deactivate_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $payload = [
            'surname' => 'Cruz', 'first_name' => 'Ana', 'email' => $admin->email,
            'role' => 'staff', 'subdivision_id' => $subdivision->getKey(), 'is_active' => true,
        ];
        $this->actingAs($admin)->put(route('users.update', $admin), $payload)->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->isAdmin());
        $payload['role'] = 'admin';
        $payload['is_active'] = false;
        $this->put(route('users.update', $admin), $payload)->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_inactive_accounts_cannot_login_and_can_login_after_activation(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact the admin.']);
        $this->assertGuest();
        $this->withCookie('quick_login_id', (string) $user->getKey())->post(route('quick-login'))->assertRedirect(route('login'));
        $this->assertGuest();
        $user->update(['is_active' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_deactivated_account_loses_access_to_an_existing_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $user->update(['is_active' => false]);
        $this->get(route('dashboard'))->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account has been deactivated. Please contact the admin.');
        $this->assertGuest();
        $this->assertNull($user->fresh()->deleted_at);
    }
}

