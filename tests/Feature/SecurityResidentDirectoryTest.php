<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityResidentDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_directory_records_and_status_are_admin_only(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'West Ridge', 'status' => 'Active']);
        $inactive = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Former Resident',
            'phone' => '09991234567',
            'status' => 'Inactive',
        ]);
        $active = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Current Resident',
            'status' => 'Active',
        ]);

        foreach (['security', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role, 'subdivision_id' => $subdivision->subdivision_id]);
            $this->actingAs($user)->get(route('residents.index', ['status' => 'Inactive']))
                ->assertOk()->assertSee('Current Resident')->assertDontSee('Former Resident')
                ->assertDontSee('09991234567')->assertDontSee('name="status"', false)
                ->assertDontSee('>Status</th>', false);
            $this->get(route('residents.show', $inactive))->assertNotFound();
            $this->get(route('residents.show', $active))->assertOk()->assertDontSee('Active');
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('residents.index', ['status' => 'Inactive']))
            ->assertOk()->assertSee('Former Resident')->assertDontSee('Current Resident')
            ->assertSee('name="status"', false);
        $this->get(route('residents.show', $inactive))->assertOk()->assertSee('Inactive');
    }

    public function test_security_can_search_and_call_only_residents_in_their_subdivision(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'West Ridge', 'status' => 'Active']);
        $otherSubdivision = Subdivision::create(['subdivision_name' => 'East Ridge', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->subdivision_id]);
        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Ana Rivera',
            'phone' => '09171234567',
            'status' => 'Active',
        ]);
        Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Leo Santos',
            'status' => 'Active',
        ]);
        $otherResident = Resident::create([
            'subdivision_id' => $otherSubdivision->subdivision_id,
            'full_name' => 'Ana Garcia',
            'phone' => '09991234567',
            'status' => 'Active',
        ]);

        $this->actingAs($security)->get(route('residents.index', ['q' => 'Ana', 'subdivision_id' => $otherSubdivision->subdivision_id]))
            ->assertOk()
            ->assertSee('Ana Rivera')
            ->assertSee('09171234567')->assertDontSee('href="tel:', false)
            ->assertDontSee('Ana Garcia')
            ->assertDontSee('Leo Santos')
            ->assertDontSee('Add Resident');

        $this->get(route('residents.show', $resident))->assertOk()->assertSee('09171234567')->assertDontSee('href="tel:', false);
        $this->get(route('residents.show', $otherResident))->assertRedirect(route('dashboard'));
        $this->post(route('residents.store'), [])->assertRedirect(route('dashboard'));
        $this->put(route('residents.update', $resident), ['full_name' => 'Changed'])->assertRedirect(route('dashboard'));
        $this->delete(route('residents.destroy', $resident))->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('residents', ['resident_id' => $resident->resident_id, 'full_name' => 'Ana Rivera']);

        $this->get(route('residents.index', ['q' => 'Leo']))
            ->assertOk()->assertSee('No phone provided')->assertDontSee('href="tel:', false);
    }
}
