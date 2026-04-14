<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_forced_password_change_user_is_redirected_to_profile_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'resident@example.com',
            'requires_password_change' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('warning', 'Please change your password before continuing.');
    }

    public function test_forced_password_change_user_cannot_access_dashboard_until_password_is_changed(): void
    {
        $user = User::factory()->create([
            'requires_password_change' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response
            ->assertRedirect('/profile')
            ->assertSessionHas('warning', 'Please change your password before continuing.');
    }
}
