<?php

namespace Tests\Feature;

use App\Models\{House, Resident, Subdivision, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_marks_owner_deceased_and_assigns_successor_once(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Transfer Estate', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'block' => '1', 'lot' => '1']);
        $admin = User::factory()->create(['role' => 'admin']);
        $base = ['subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(), 'phone' => '09123456789', 'email' => 'resident@example.com', 'status' => 'Active'];
        $owner = Resident::create($base + ['full_name' => 'Former Owner', 'relation_to_owner' => 'Owner']);
        Resident::create($base + ['full_name' => 'Second Owner', 'relation_to_owner' => 'Owner']);
        $wife = Resident::create($base + ['full_name' => 'Living Spouse', 'relation_to_owner' => 'Wife']);
        $payload = $base + [
            'full_name' => $owner->full_name, 'address_or_unit' => '', 'relation_to_owner' => 'Owner', 'is_deceased' => '1',
        ];
        $this->actingAs($admin)->put(route('residents.update', $owner), $payload)->assertSessionHasErrors('relation_to_owner');
        $this->assertFalse($owner->fresh()->is_deceased);
        $payload['relation_to_owner'] = 'Former Owner';
        $this->put(route('residents.update', $owner), $payload)->assertSessionHasErrors('successor_id');
        $this->assertFalse($owner->fresh()->is_deceased);
        $payload['successor_id'] = $wife->getKey();
        $payload['confirm_transfer'] = '1';
        $this->put(route('residents.update', $owner), $payload)->assertSessionHasNoErrors();
        $this->assertTrue($owner->fresh()->is_deceased);
        $this->assertSame('Inactive', $owner->fresh()->status->value);
        $this->assertSame('Former Owner', $owner->fresh()->relation_to_owner);
        $this->get(route('residents.show', $owner))->assertOk()->assertSee('Ownership assigned to');
        $this->assertSame('Owner', $wife->fresh()->relation_to_owner);
        $this->assertDatabaseHas('house_ownership_transfers', ['from_resident_id' => $owner->getKey(), 'to_resident_id' => $wife->getKey(), 'previous_relationship' => 'Wife']);
        $this->post(route('residents.transfer-ownership', $owner), ['successor_id' => $wife->getKey(), 'confirm_transfer' => '1'])->assertSessionHasErrors('successor_id');
        $this->assertDatabaseCount('house_ownership_transfers', 1);
    }

    public function test_transfer_rejects_other_house_and_non_admin(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Transfer Estate', 'status' => 'Active']);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'block' => '1', 'lot' => '1']);
        $base = ['subdivision_id' => $subdivision->getKey(), 'phone' => '09123456789', 'email' => 'resident@example.com', 'status' => 'Active'];
        $owner = Resident::create($base + ['house_id' => $house->getKey(), 'full_name' => 'Former Owner', 'relation_to_owner' => 'Owner', 'is_deceased' => true]);
        $other = Resident::create($base + ['full_name' => 'Other Resident', 'relation_to_owner' => 'Child']);
        $payload = ['successor_id' => $other->getKey(), 'confirm_transfer' => '1'];
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post(route('residents.transfer-ownership', $owner), $payload)->assertSessionHasErrors('successor_id');
        $this->actingAs(User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]))->post(route('residents.transfer-ownership', $owner), $payload)->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('house_ownership_transfers', 0);
    }
}
