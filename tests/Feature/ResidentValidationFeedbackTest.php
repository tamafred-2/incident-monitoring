<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentValidationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_creation_shows_errors_and_preserves_values_in_the_create_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        $response = $this->actingAs($admin)->from(route('residents.index'))->post(route('residents.store'), [
            'surname' => 'Cruz1', 'first_name' => 'Ana2', 'middle_name' => 'Maria3', 'extension' => '4',
            'phone' => 'abc', 'email' => 'invalid', 'subdivision_id' => $subdivision->getKey(),
        ]);
        $response->assertSessionHasErrors(['surname', 'first_name', 'middle_name', 'extension', 'phone', 'email']);
        $this->get(route('residents.index'))->assertOk()
            ->assertDontSee('Resident could not be saved. Please correct the following:')
            ->assertSee('numbers are not allowed.')
            ->assertSee('value="Ana2"', false)
            ->assertSee('id="resident-create-first_name-error"', false)
            ->assertSee('id="resident-create-phone-error"', false)
            ->assertSee('id="resident-create-email-error"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('show: true', false);
    }

    public function test_failed_edit_reopens_a_resident_outside_the_loaded_batch_without_leaking_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subdivision = Subdivision::create(['subdivision_name' => 'Palm Grove', 'status' => 'Active']);
        foreach (range('A', 'K') as $letter) {
            Resident::create(['full_name' => $letter.' Cruz', 'subdivision_id' => $subdivision->getKey(), 'status' => 'Active']);
        }
        $resident = Resident::create([
            'full_name' => 'Zoe Cruz', 'subdivision_id' => $subdivision->getKey(), 'status' => 'Active',
        ]);
        $this->actingAs($admin)->from(route('residents.index'))->put(route('residents.update', $resident), [
            'edit_resident_id' => $resident->getKey(), 'surname' => 'Cruz', 'first_name' => 'Zoe1',
            'phone' => '09171234567', 'email' => 'zoe@example.com', 'subdivision_id' => $subdivision->getKey(),
        ])->assertSessionHasErrors('first_name');

        $response = $this->get(route('residents.index'))->assertOk()
            ->assertSee('id="resident-'.$resident->getKey().'-first_name-error"', false)
            ->assertDontSee('id="resident-create-first_name-error"', false)
            ->assertSee('value="Zoe1"', false)
            ->assertSee("edit-resident-{$resident->getKey()}", false)
            ->assertSee('show: true', false);
        $this->assertCount(10, $response->viewData('residents'));
        $response->assertDontSee('Resident could not be saved.');
        $this->assertSame('Zoe Cruz', $resident->fresh()->full_name);
    }
}
