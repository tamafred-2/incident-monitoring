<?php

namespace Tests\Feature;

use App\Models\{House, Subdivision, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DuplicateCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_form_modal_is_closed_but_can_open_on_validation_failure(): void
    {
        $this->withSession(['form_saved' => true]);
        $template = '<x-modal name="create-record" :show="true">Form contents</x-modal>';
        $html = \Illuminate\Support\Facades\Blade::render($template);
        $this->assertStringContainsString('show: false', $html);
        session()->forget('form_saved');
        $html = \Illuminate\Support\Facades\Blade::render($template);
        $this->assertStringContainsString('show: true', $html);
    }

    public function test_repeated_form_creates_one_incident_and_validation_can_be_retried(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'Test', 'status' => 'Active']);
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->subdivision_id]);
        $house = House::create(['subdivision_id' => $subdivision->subdivision_id, 'block' => '2', 'lot' => '1']);
        $payload = [
            '_submission_token' => str_repeat('a', 32),
            'subdivision_id' => $subdivision->subdivision_id, 'house_id' => $house->house_id,
            'description' => 'Broken gate', 'status' => 'Open',
            'incident_date' => now()->subMinute()->toDateTimeString(), 'reported_at' => now()->toDateTimeString(),
        ];
        $this->actingAs($staff)->post(route('incidents.store'), array_replace($payload, ['description' => '']))->assertSessionHasErrors('description');
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('incidents.store'), $payload)->assertRedirect(route('incidents.index'))
                ->assertSessionHas('form_saved', true)->assertSessionMissing('errors')->assertSessionMissing('_old_input');
        }
        $this->assertDatabaseCount('incidents', 1);
        $this->post(route('incidents.store'), array_replace($payload, ['_submission_token' => str_repeat('b', 32)]))->assertRedirect(route('incidents.index'));
        $this->assertDatabaseCount('incidents', 2);

        $payload['_submission_token'] = str_repeat('c', 32);
        $key = 'form-submission:' . hash('sha256', $staff->user_id . '|incidents.store|' . $payload['_submission_token']);
        $lock = Cache::lock($key . ':lock', 120);
        $lock->get();
        try {
            $this->post(route('incidents.store'), $payload)->assertSessionHas('error');
            $this->assertDatabaseCount('incidents', 2);
        } finally {
            $lock->release();
        }
    }
}
