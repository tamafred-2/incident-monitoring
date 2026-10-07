<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'surname' => 'User',
                'first_name' => 'Test',
                'middle_name' => 'Sample',
                'extension' => 'Jr.',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('User', $user->surname);
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('Sample', $user->middle_name);
        $this->assertSame('Jr.', $user->extension);
        $this->assertSame('Test Sample User Jr.', $user->full_name);
        $this->assertSame('test@example.com', $user->email);
    }

    public function test_profile_can_be_updated_without_changing_the_email_address(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'surname' => 'User',
                'first_name' => 'Test',
                'middle_name' => null,
                'extension' => null,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Test User', $user->refresh()->full_name);
    }

    public function test_account_deletion_is_unavailable_from_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/profile')->assertOk()->assertDontSee('Delete Account');
        $this->delete('/profile', ['password' => 'password'])->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }

    public function test_users_cannot_change_their_account_status_through_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/profile', [
            'surname' => 'Cruz', 'first_name' => 'Ana', 'email' => $user->email, 'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->is_active);
    }
}
