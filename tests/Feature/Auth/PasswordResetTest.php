<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_request_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_password_reset_request_is_recorded_for_an_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $user->user_id,
            'email' => $user->email,
            'status' => PasswordResetRequest::STATUS_PENDING,
        ]);
    }

    public function test_password_reset_request_rejects_unknown_email(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_admin_can_resolve_a_password_reset_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['requires_password_change' => false]);

        $resetRequest = PasswordResetRequest::create([
            'user_id' => $user->user_id,
            'email' => $user->email,
            'status' => PasswordResetRequest::STATUS_PENDING,
            'expires_at' => now()->addMinutes(PasswordResetRequest::GRACE_MINUTES),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.password-resets.resolve', $resetRequest));

        $response->assertSessionHas('reset_password_reveal');

        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $resetRequest->id,
            'status' => PasswordResetRequest::STATUS_COMPLETED,
            'resolved_by' => $admin->user_id,
        ]);
        $this->assertTrue($user->fresh()->requires_password_change);
    }
}
