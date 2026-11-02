<?php

namespace Tests\Feature;

use App\Models\GuardDevice;
use App\Models\Subdivision;
use App\Models\User;
use App\Support\GuardAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardAccessTest extends TestCase
{
    use RefreshDatabase;

    private Subdivision $subdivision;

    protected function setUp(): void
    {
        parent::setUp();
        config(['guard_access.require_device' => true]);
        $this->subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
    }

    private function guard(): User
    {
        return User::factory()->create([
            'role' => 'security', 'subdivision_id' => $this->subdivision->getKey(),
            'email' => 'guard@example.com', 'password' => 'secret-pass',
        ]);
    }

    private function deviceTokenFrom($response): string
    {
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === GuardAccess::DEVICE_COOKIE);
        $this->assertNotNull($cookie, 'Device cookie was not set.');

        return $cookie->getValue();
    }

    public function test_guard_on_a_new_device_is_refused_until_an_admin_approves_it(): void
    {
        $guard = $this->guard();

        $first = $this->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
        $device = GuardDevice::sole();
        $this->assertSame(GuardDevice::STATUS_PENDING, $device->status);
        // The response cookie is already encrypted, so it is sent back as-is.
        $token = $this->deviceTokenFrom($first);

        // Same device, still pending: no second request is created.
        $this->withUnencryptedCookie(GuardAccess::DEVICE_COOKIE, $token)
            ->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass'])
            ->assertSessionHas('error');
        $this->assertGuest();
        $this->assertSame(1, GuardDevice::count());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.guard-devices.index'))->assertOk()->assertSee($guard->full_name);
        $this->post(route('admin.guard-devices.approve', $device))->assertSessionHas('success');
        $this->post(route('logout'));

        $this->withUnencryptedCookie(GuardAccess::DEVICE_COOKIE, $token)
            ->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($guard);
    }

    public function test_removing_a_device_signs_the_guard_out(): void
    {
        $guard = $this->guard();
        $token = str_repeat('a', 64);
        $device = GuardDevice::create([
            'user_id' => $guard->user_id, 'token_hash' => hash('sha256', $token),
            'status' => GuardDevice::STATUS_APPROVED, 'approved_at' => now(),
        ]);

        $this->actingAs($guard)->withCookie(GuardAccess::DEVICE_COOKIE, $token)
            ->get(route('dashboard'))->assertOk();

        $device->delete();
        $this->withCookie(GuardAccess::DEVICE_COOKIE, $token)
            ->get(route('dashboard'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_other_roles_are_not_device_restricted(): void
    {
        User::factory()->create(['role' => 'staff', 'email' => 'staff@example.com', 'password' => 'secret-pass']);

        $this->post(route('login'), ['email' => 'staff@example.com', 'password' => 'secret-pass'])
            ->assertRedirect(route('dashboard'));
        $this->assertSame(0, GuardDevice::count());
    }

    public function test_guard_outside_the_premises_cannot_sign_in(): void
    {
        config(['guard_access.require_device' => false]);
        $this->subdivision->update(['latitude' => 14.6760, 'longitude' => 121.0437, 'premises_radius_m' => 300]);
        $guard = $this->guard();

        $this->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'location is required'));
        $this->assertGuest();

        // About 1.1 km north of the pin.
        $this->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass', 'latitude' => 14.6860, 'longitude' => 121.0437, 'accuracy' => 20])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'outside'));
        $this->assertGuest();

        $this->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass', 'latitude' => 14.6765, 'longitude' => 121.0437, 'accuracy' => 20])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($guard);

        $this->postJson(route('guard.location'), ['latitude' => 14.6762, 'longitude' => 121.0438, 'accuracy' => 10])->assertOk();
        $this->postJson(route('guard.location'), ['latitude' => 14.7000, 'longitude' => 121.0437, 'accuracy' => 10])
            ->assertStatus(401)->assertJsonPath('redirect', route('login'));
        $this->assertGuest();
    }

    public function test_guard_is_signed_out_when_location_is_not_reconfirmed(): void
    {
        config(['guard_access.require_device' => false]);
        $this->subdivision->update(['latitude' => 14.6760, 'longitude' => 121.0437]);
        $guard = $this->guard();

        $this->post(route('login'), ['email' => $guard->email, 'password' => 'secret-pass', 'latitude' => 14.6760, 'longitude' => 121.0437]);
        $this->get(route('dashboard'))->assertOk();

        $this->travel(config('guard_access.location_ttl_minutes') + 1)->minutes();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_sets_and_clears_the_premises_pin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put(route('admin.guard-devices.premises'), ['latitude' => '14.6760000', 'longitude' => '121.0437000', 'premises_radius_m' => 250])
            ->assertSessionHasNoErrors();
        $this->assertSame(250, $this->subdivision->fresh()->premises_radius_m);
        $this->assertTrue(GuardAccess::premisesCheckEnabled());

        $this->put(route('admin.guard-devices.premises'), ['latitude' => '', 'longitude' => ''])->assertSessionHasNoErrors();
        $this->assertFalse(GuardAccess::premisesCheckEnabled());
    }

    public function test_guard_cannot_manage_devices(): void
    {
        $this->actingAs($this->guard());
        config(['guard_access.require_device' => false]);
        $this->get(route('admin.guard-devices.index'))->assertRedirect(route('dashboard'));
    }
}
