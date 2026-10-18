<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_house_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('houses.store'), [
                'owner' => ['surname' => 'Cruz', 'first_name' => 'Ana', 'middle_name' => 'Reyes', 'extension' => 'Jr.', 'phone' => '09171234567', 'email' => 'ana@example.test'],
                'subdivision_id' => $subdivision->subdivision_id,
                'street' => 'Main Street',
                'block' => '3',
                'lot' => '12',
            ]);

        $response
            ->assertRedirect(route('houses.index'))
            ->assertSessionHas('success', 'House added successfully.');

        $this->assertDatabaseHas('houses', [
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '12',
        ]);
        $house = House::sole();
        $this->assertDatabaseHas('residents', [
            'house_id' => $house->house_id, 'subdivision_id' => $subdivision->subdivision_id,
            'full_name' => 'Ana Reyes Cruz Jr.', 'phone' => '09171234567', 'email' => 'ana@example.test',
            'relation_to_owner' => 'Owner', 'status' => 'Active', 'address_or_unit' => $house->display_address,
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->actingAs($admin)->get(route('houses.index'))->assertOk()->assertSee('Ana Reyes Cruz Jr.');
    }

    public function test_owner_information_is_required_and_invalid_input_does_not_create_a_house(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Northview', 'status' => 'Active']);
        $url = route('subdivisions.show', $subdivision);
        $this->actingAs($admin)->from($url)->post(route('houses.store'), [
            'subdivision_id' => $subdivision->getKey(), 'street' => 'Main Street', 'block' => '1', 'lot' => '2',
            'owner' => ['surname' => 'Cruz1', 'phone' => 'abc', 'email' => 'invalid'],
        ])->assertRedirect($url)->assertSessionHasErrors([
            'owner.surname', 'owner.first_name', 'owner.phone', 'owner.email',
        ], null, 'houseCreate');
        $this->assertDatabaseCount('houses', 0);
        $this->assertDatabaseCount('residents', 0);
        $this->get($url)->assertOk()->assertSee('name="owner[surname]"', false)->assertSee('value="Cruz1"', false);
    }

    public function test_house_creation_is_rolled_back_if_the_owner_cannot_be_saved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Northview', 'status' => 'Active']);
        Resident::creating(function (): void {
            throw new \RuntimeException('Simulated resident save failure');
        });
        $this->actingAs($admin)->post(route('houses.store'), [
            'subdivision_id' => $subdivision->getKey(), 'street' => 'Main Street', 'block' => '1', 'lot' => '2',
            'owner' => ['surname' => 'Cruz', 'first_name' => 'Ana', 'phone' => '09171234567', 'email' => 'ana@example.test'],
        ])->assertServerError();
        $this->assertDatabaseCount('houses', 0);
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_admin_can_view_a_house_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '7',
            'lot' => '4',
        ]);

        $this->actingAs($admin)
            ->get(route('houses.show', $house))
            ->assertOk()
            ->assertSee('Record Details')
            ->assertSee('Block 7 Lot 4');
    }

    public function test_house_detail_page_shows_assigned_residents(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '8',
            'lot' => '2',
        ]);

        Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'full_name' => 'Lina Cruz',
            'resident_code' => 'RES-404',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('houses.show', $house))
            ->assertOk()
            ->assertSee('Residents')
            ->assertSee('Lina Cruz');
    }

    public function test_house_record_must_be_unique_within_a_subdivision(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Southridge',
            'status' => 'Active',
        ]);

        House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'street' => 'Main Street',
            'block' => 'A',
            'lot' => '7',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('houses.index'))
            ->post(route('houses.store'), [
                'owner' => ['surname' => 'Cruz', 'first_name' => 'Ana', 'middle_name' => 'Reyes', 'extension' => 'Jr.', 'phone' => '09171234567', 'email' => 'ana@example.test'],
                'subdivision_id' => $subdivision->subdivision_id,
                'street' => 'Main Street',
                'block' => 'A',
                'lot' => '7',
            ]);

        $response
            ->assertRedirect(route('houses.index'))
            ->assertSessionHasErrors(['lot'], null, 'houseCreate');
    }

    public function test_same_block_and_lot_can_exist_in_different_subdivisions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $firstSubdivision = Subdivision::create([
            'subdivision_name' => 'Eastfield',
            'status' => 'Active',
        ]);

        $secondSubdivision = Subdivision::create([
            'subdivision_name' => 'Westfield',
            'status' => 'Active',
        ]);

        House::create([
            'subdivision_id' => $firstSubdivision->subdivision_id,
            'street' => 'Main Street',
            'block' => '5',
            'lot' => '9',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('houses.store'), [
                'owner' => ['surname' => 'Cruz', 'first_name' => 'Ana', 'middle_name' => 'Reyes', 'extension' => 'Jr.', 'phone' => '09171234567', 'email' => 'ana@example.test'],
                'subdivision_id' => $secondSubdivision->subdivision_id,
                'street' => 'Main Street',
                'block' => '5',
                'lot' => '9',
            ]);

        $response->assertRedirect(route('houses.index'));

        $this->assertDatabaseCount('houses', 2);
    }

    public function test_admin_can_delete_a_house_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Brookside',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '2',
            'lot' => '4',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('houses.destroy', $house));

        $response
            ->assertRedirect(route('houses.index'))
            ->assertSessionHas('success', 'House deleted successfully.');

        $this->assertDatabaseMissing('houses', [
            'house_id' => $house->house_id,
        ]);
    }
}
