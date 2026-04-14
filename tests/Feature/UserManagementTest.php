<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_detail_page_can_be_viewed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'subdivision_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('users.show', $targetUser))
            ->assertOk()
            ->assertSee('User Details')
            ->assertSee($targetUser->full_name);
    }

    public function test_admin_can_create_resident_account_linked_to_resident_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Lakeside',
            'status' => 'Active',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'block' => '1',
                'lot' => '3',
            ])->house_id,
            'full_name' => 'Mia Santos',
            'resident_code' => 'RES-5001',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'surname' => 'Santos',
                'first_name' => 'Mia',
                'middle_name' => '',
                'extension' => '',
                'email' => 'mia@example.com',
                'role' => 'resident',
                'resident_id' => $resident->resident_id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'mia@example.com',
            'role' => 'resident',
            'resident_id' => $resident->resident_id,
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
    }

    public function test_admin_cannot_create_resident_account_without_house_linked_resident_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Green Meadows',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'surname' => 'Lopez',
                'first_name' => 'Carla',
                'middle_name' => '',
                'extension' => '',
                'email' => 'carla@example.com',
                'role' => 'resident',
                'resident_id' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertSessionHasErrors('resident_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'carla@example.com',
        ]);
    }

    public function test_admin_can_create_resident_account_with_new_resident_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Maple Grove',
            'status' => 'Active',
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '1',
            'lot' => '5',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'surname' => 'Test',
                'first_name' => 'Tina',
                'middle_name' => '',
                'extension' => '',
                'email' => 'tina@example.com',
                'role' => 'resident',
                'resident_mode' => 'new',
                'new_resident_subdivision_id' => $subdivision->subdivision_id,
                'new_resident_house_id' => $house->house_id,
                'new_resident_phone' => '09879089989',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User created successfully.');

        $resident = Resident::where('email', 'tina@example.com')->first();

        $this->assertNotNull($resident);
        $this->assertSame($subdivision->subdivision_id, $resident->subdivision_id);
        $this->assertSame($house->house_id, $resident->house_id);
        $this->assertSame($house->display_address, $resident->address_or_unit);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{6}$/', $resident->resident_code);

        $this->assertDatabaseHas('users', [
            'email' => 'tina@example.com',
            'role' => 'resident',
            'resident_id' => $resident->resident_id,
            'subdivision_id' => $subdivision->subdivision_id,
        ]);
    }

    public function test_admin_can_link_resident_when_previous_account_is_archived(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Riverside',
            'status' => 'Active',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'block' => '2',
                'lot' => '7',
            ])->house_id,
            'full_name' => 'Nina Cruz',
            'resident_code' => 'RES-7001',
            'status' => 'Active',
        ]);

        User::factory()->create([
            'role' => 'resident',
            'email' => 'archived-resident@example.com',
            'resident_id' => $resident->resident_id,
            'subdivision_id' => $subdivision->subdivision_id,
        ])->delete();

        $response = $this
            ->actingAs($admin)
            ->post(route('users.store'), [
                'surname' => 'Cruz',
                'first_name' => 'Nina',
                'middle_name' => '',
                'extension' => '',
                'email' => 'nina@example.com',
                'role' => 'resident',
                'resident_mode' => 'existing',
                'resident_id' => $resident->resident_id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'nina@example.com',
            'resident_id' => $resident->resident_id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_deletion_uses_soft_deletes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('users.destroy', $targetUser));

        $response
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User archived successfully.');

        $this->assertSoftDeleted('users', [
            'user_id' => $targetUser->user_id,
        ]);
    }

    public function test_archived_user_can_be_restored(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $targetUser->delete();

        $response = $this
            ->actingAs($admin)
            ->post(route('users.restore', $targetUser->user_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('users.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'User restored successfully.');

        $this->assertDatabaseHas('users', [
            'user_id' => $targetUser->user_id,
            'deleted_at' => null,
        ]);
    }

    public function test_archived_user_can_be_force_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $targetUser = User::factory()->create([
            'role' => 'staff',
            'subdivision_id' => null,
        ]);

        $targetUser->delete();

        $response = $this
            ->actingAs($admin)
            ->delete(route('users.force-delete', $targetUser->user_id), [
                'view' => 'deleted',
            ]);

        $response
            ->assertRedirect(route('users.index', ['view' => 'deleted']))
            ->assertSessionHas('success', 'User permanently deleted.');

        $this->assertDatabaseMissing('users', [
            'user_id' => $targetUser->user_id,
        ]);
    }
}
