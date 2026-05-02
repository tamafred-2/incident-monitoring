<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VisitorCheckInFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_can_submit_resident_visit_request(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $house = House::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'block' => '3',
            'lot' => '12',
        ]);

        $resident = Resident::create([
            'subdivision_id' => $subdivision->subdivision_id,
            'house_id' => $house->house_id,
            'full_name' => 'Juan Dela Cruz',
            'phone' => '09171234567',
            'status' => 'Active',
        ]);

        $response = $this
            ->actingAs($security)
            ->post(route('visitors.store'), [
                'visit_type' => 'resident',
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Cruz',
                'first_name' => 'Ana',
                'phone' => '09181234567',
                'purpose' => 'Family visit',
                'on_vehicle' => 1,
                'plate_number' => 'ABC 1234',
                'passenger_count' => 3,
                'id_photo' => UploadedFile::fake()->create('visitor-id.jpg', 120, 'image/jpeg'),
                'house_address_or_unit' => 'Block 3 Lot 12',
                'resident_id' => $resident->resident_id,
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visitor_requests', [
            'subdivision_id' => $subdivision->subdivision_id,
            'resident_id' => $resident->resident_id,
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'house_address_or_unit' => 'Block 3 Lot 12',
            'plate_number' => 'ABC 1234',
            'passenger_count' => 3,
            'status' => 'Pending',
        ]);

        $this->assertDatabaseMissing('visitors', [
            'surname' => 'Cruz',
            'first_name' => 'Ana',
        ]);
    }

    public function test_security_can_check_in_walk_in_visitor_without_resident(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Northview',
            'status' => 'Active',
        ]);

        $security = User::factory()->create([
            'role' => 'security',
            'subdivision_id' => $subdivision->subdivision_id,
        ]);

        $response = $this
            ->actingAs($security)
            ->post(route('visitors.store'), [
                'visit_type' => 'walk_in',
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Santos',
                'first_name' => 'Leo',
                'phone' => '09221234567',
                'purpose' => 'Basketball game',
                'on_vehicle' => 1,
                'plate_number' => 'XYZ 9876',
                'passenger_count' => 4,
                'id_photo' => UploadedFile::fake()->create('walk-in-id.jpg', 120, 'image/jpeg'),
                'house_address_or_unit' => 'Clubhouse Court',
            ]);

        $response
            ->assertSessionHas('success', 'Walk-in visitor checked in successfully.');

        $this->assertDatabaseHas('visitors', [
            'subdivision_id' => $subdivision->subdivision_id,
            'surname' => 'Santos',
            'first_name' => 'Leo',
            'house_address_or_unit' => 'Clubhouse Court',
            'plate_number' => 'XYZ 9876',
            'passenger_count' => 4,
            'status' => 'Inside',
        ]);

        $this->assertDatabaseMissing('visitor_requests', [
            'surname' => 'Santos',
            'first_name' => 'Leo',
        ]);
    }
}
