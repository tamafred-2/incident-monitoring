<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_resident_assigned_to_a_house(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Palm Grove',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '4',
            'lot' => '11',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('residents.store'), [
                'surname' => 'Ramos',
                'first_name' => 'Lea',
                'middle_name' => '',
                'extension' => '',
                'phone' => '09171234567',
                'email' => 'lea@example.com',
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house->house_id,
                'address_or_unit' => '',
                'resident_code' => 'RES-7001',
                'status' => 'Active',
            ]);

        $response
            ->assertRedirect(route('residents.index'))
            ->assertSessionHas('success', 'Resident created successfully.');

        $this->assertDatabaseHas('residents', [
            'full_name' => 'Lea Ramos',
            'house_id' => $house->house_id,
            'address_or_unit' => 'Block 4 Lot 11',
        ]);
    }

    public function test_authorized_user_can_view_resident_details(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'East Ridge',
            'status' => 'Active',
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Nina Flores',
            'resident_code' => 'RES-7002',
            'status' => 'Active',
        ]);

        $this->actingAs($staff)
            ->get(route('residents.show', $resident))
            ->assertOk()
            ->assertSee('Resident Details')
            ->assertSee('Nina Flores');
    }

    public function test_admin_can_update_a_resident_house_assignment(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'West Ridge',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '2',
            'lot' => '8',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Paolo Reyes',
            'resident_code' => 'RES-7003',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('residents.update', $resident), [
                'surname' => 'Reyes',
                'first_name' => 'Paolo',
                'middle_name' => '',
                'extension' => '',
                'phone' => '',
                'email' => '',
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house->house_id,
                'address_or_unit' => '',
                'resident_code' => 'RES-7003',
                'status' => 'Active',
            ]);

        $response
            ->assertRedirect(route('residents.index'))
            ->assertSessionHas('success', 'Resident updated successfully.');

        $this->assertDatabaseHas('residents', [
            'resident_id' => $resident->resident_id,
            'house_id' => $house->house_id,
            'address_or_unit' => 'Block 2 Lot 8',
        ]);
    }

    public function test_admin_can_delete_resident_without_linked_account_or_incidents(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Southview',
            'status' => 'Active',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Diane Cruz',
            'resident_code' => 'RES-7004',
            'status' => 'Inactive',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('residents.destroy', $resident));

        $response
            ->assertRedirect(route('residents.index'))
            ->assertSessionHas('success', 'Resident deleted successfully.');

        $this->assertDatabaseMissing('residents', [
            'resident_id' => $resident->resident_id,
        ]);
    }

    public function test_admin_cannot_delete_resident_with_linked_incident_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Arvin Diaz',
            'resident_code' => 'RES-7005',
            'status' => 'Active',
        ]);

        Incident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'block' => '9',
                'lot' => '9',
            ])->house_id,
            'description' => 'Loud music after hours',
            'category' => 'Noise',
            'location' => 'Clubhouse',
            'incident_date' => now(),
            'reported_at' => now(),
            'status' => 'Open',
            'reported_by' => $admin->user_id,
            'verified_resident_id' => $resident->resident_id,
            'verification_method' => 'manual',
            'verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('residents.destroy', $resident));

        $response
            ->assertRedirect(route('residents.index'))
            ->assertSessionHas('error', 'Residents with verified incident records cannot be deleted.');

        $this->assertDatabaseHas('residents', [
            'resident_id' => $resident->resident_id,
        ]);
    }
}
