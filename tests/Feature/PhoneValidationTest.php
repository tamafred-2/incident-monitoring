<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_fields_require_exactly_eleven_digits(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Northview', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin', 'subdivision_id' => $subdivision->getKey()]);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);

        foreach ([
            [$admin, 'POST', route('residents.store'), 'phone'],
            [$admin, 'POST', route('houses.store'), 'owner.phone'],
            [$security, 'POST', route('visitors.store'), 'phone'],
            [$security, 'POST', route('visitor-profiles.store'), 'phone'],
            [$admin, 'PUT', route('subdivisions.update', $subdivision), 'contact_number'],
            [$admin, 'PUT', route('subdivisions.update', $subdivision), 'secondary_contact_number'],
        ] as [$user, $method, $url, $field]) {
            $this->actingAs($user);
            foreach (['0907232772', '090723277211', '0907232772a', '+9072327721', '09072 27721'] as $phone) {
                $this->json($method, $url, Arr::undot([$field => $phone]))
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
            }

            // Other required fields are omitted so these requests only exercise validation.
            $this->json($method, $url, Arr::undot([$field => '09072327721']))
                ->assertUnprocessable()->assertJsonMissingValidationErrors($field);
        }

        $this->actingAs($admin)->putJson(route('subdivisions.update', $subdivision), ['secondary_contact_number' => ''])
            ->assertUnprocessable()->assertJsonMissingValidationErrors('secondary_contact_number');
    }
}
