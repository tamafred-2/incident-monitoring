<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Rules\PersonName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PersonNameValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_and_updates_reject_numbers_in_all_name_parts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'surname' => 'Cruz1', 'first_name' => 'Ana2',
            'middle_name' => 'Maria3', 'extension' => 'Jr4',
            'email' => 'invalid@example.com', 'role' => 'admin',
        ];

        $this->actingAs($admin)->post(route('users.store'), $payload)
            ->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension']);
        $this->put(route('users.update', $admin), $payload)
            ->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension']);
        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    }

    public function test_profile_rejects_numeric_names_and_accepts_accented_and_punctuated_names(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), [
            'surname' => 'Cruz1', 'first_name' => 'Ana2',
            'middle_name' => 'Maria3', 'extension' => '4', 'email' => $user->email,
        ])->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension']);

        $this->patch(route('profile.update'), [
            'surname' => 'O’Connor', 'first_name' => 'José-Anne',
            'middle_name' => 'María', 'extension' => 'Jr.', 'email' => $user->email,
        ])->assertSessionHasNoErrors();
        $this->assertSame('José-Anne', $user->fresh()->first_name);
    }

    public function test_resident_creation_and_updates_reject_numeric_name_parts_and_full_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $resident = Resident::create([
            'full_name' => 'Ana Cruz', 'subdivision_id' => $subdivision->getKey(), 'status' => 'Active',
        ]);
        $payload = [
            'surname' => 'Cruz1', 'first_name' => 'Ana2', 'middle_name' => 'Maria3',
            'extension' => '4', 'phone' => '09171234567', 'email' => 'invalid@example.com',
            'subdivision_id' => $subdivision->getKey(),
        ];

        $this->actingAs($admin)->post(route('residents.store'), $payload)
            ->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension']);
        $this->put(route('residents.update', $resident), $payload)
            ->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension']);

        unset($payload['surname'], $payload['first_name'], $payload['middle_name'], $payload['extension']);
        $payload['full_name'] = 'Ana Cruz1';
        $this->post(route('residents.store'), $payload)->assertSessionHasErrors('full_name');
        $this->put(route('residents.update', $resident), $payload)->assertSessionHasErrors('full_name');
        $this->assertSame('Ana Cruz', $resident->fresh()->full_name);
    }

    public function test_visitor_creation_and_updates_reject_numeric_names_initials_suffix_and_host(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $visitor = Visitor::create([
            'surname' => 'Cruz', 'first_name' => 'Ana', 'subdivision_id' => $subdivision->getKey(),
            'check_in' => now(), 'status' => 'Inside',
        ]);
        $payload = [
            'surname' => 'Cruz1', 'first_name' => 'Ana2', 'middle_initials' => 'M3.',
            'extension' => '4', 'host_employee' => 'Jose5', 'phone' => '09171234567',
            'visit_type' => 'walk_in', 'house_address_or_unit' => 'Clubhouse',
            'check_in' => now()->toDateTimeString(), 'status' => 'Inside',
        ];
        $fields = ['surname', 'first_name', 'middle_initials', 'extension', 'host_employee'];
        $this->actingAs($security)->post(route('visitors.store'), $payload)->assertSessionHasErrors($fields);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put(route('visitors.update', $visitor), $payload)->assertSessionHasErrors($fields);
        $this->assertSame('Ana', $visitor->fresh()->first_name);
    }

    public function test_shared_rule_supports_real_name_characters_and_rejects_unicode_numbers(): void
    {
        foreach (['Anne-Marie', "O'Connor", 'O’Connor', 'José', "Jose\u{0301}", '李明', 'De la Cruz', 'J. P.', 'Jr.', 'III'] as $name) {
            $this->assertTrue(Validator::make(['name' => $name], ['name' => [new PersonName]])->passes(), $name);
        }

        foreach (['Ana1', 'Ana١', 'Ana２', 'Ana²', '123', '---', 'Ana@'] as $name) {
            $this->assertTrue(Validator::make(['name' => $name], ['name' => [new PersonName]])->fails(), $name);
        }

        $this->assertTrue(Validator::make(['name' => null], ['name' => ['nullable', new PersonName]])->passes());
    }
}
