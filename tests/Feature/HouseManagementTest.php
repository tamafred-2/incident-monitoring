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
                'subdivision_id' => $subdivision->subdivision_id,
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
            ->assertSee('House Details')
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
            ->assertSee('Assigned Residents')
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
            'block' => 'A',
            'lot' => '7',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('houses.index'))
            ->post(route('houses.store'), [
                'subdivision_id' => $subdivision->subdivision_id,
                'block' => 'A',
                'lot' => '7',
            ]);

        $response
            ->assertRedirect(route('houses.index'))
            ->assertSessionHasErrors('block');
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
            'block' => '5',
            'lot' => '9',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('houses.store'), [
                'subdivision_id' => $secondSubdivision->subdivision_id,
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
