<?php

namespace Tests\Feature;

use App\Models\{Subdivision, User, Visitor, VisitorProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInTimeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_hours_are_validated_converted_and_notify_when_overdue(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Custom Estate', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        $profile = VisitorProfile::create(['subdivision_id' => $home->getKey(), 'first_name' => 'Custom', 'surname' => 'Visitor', 'phone' => '09123456789', 'id_photo_path' => 'sample.jpg']);
        $payload = ['visit_type' => 'walk_in', 'visitor_profile_id' => $profile->getKey(), 'house_address_or_unit' => 'Clubhouse', 'allowed_minutes' => 'other', 'custom_duration_unit' => 'hours'];
        $this->actingAs($guard);
        foreach ([null, 0, -1, 1.5, 13, 24] as $duration) {
            $this->postJson(route('visitors.store'), $payload + ['custom_duration' => $duration])->assertUnprocessable()->assertJsonValidationErrors('custom_duration');
        }
        $this->postJson(route('visitors.store'), array_replace($payload, ['custom_duration_unit' => 'minutes', 'custom_duration' => 721]))->assertUnprocessable()->assertJsonValidationErrors('custom_duration');
        $this->post(route('visitors.store'), $payload + ['custom_duration' => 2])->assertSessionHasNoErrors();
        $visitor = Visitor::firstOrFail();
        $this->assertSame(120, $visitor->allowed_minutes);
        $this->assertCount(0, \App\Support\OverdueVisitFeed::forUser($guard));
        $this->travel(121)->minutes();
        $this->assertTrue($visitor->is_overdue);
        $this->assertCount(1, \App\Support\OverdueVisitFeed::forUser($guard));
    }

    public function test_security_must_choose_an_allowed_duration_and_can_record_overdue_follow_up(): void
    {
        $this->travelTo(now()->setTime(10, 0));
        $subdivision = Subdivision::create(['subdivision_name' => 'Sample Estate', 'status' => 'Active']);
        $security = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $profile = VisitorProfile::create(['subdivision_id' => $subdivision->getKey(), 'first_name' => 'Sample', 'surname' => 'Visitor', 'phone' => '09123456789', 'id_photo_path' => 'sample.jpg']);
        $payload = ['visit_type' => 'walk_in', 'visitor_profile_id' => $profile->getKey(), 'house_address_or_unit' => 'Clubhouse'];
        $this->actingAs($security);
        foreach ([null, 0, 20, 60] as $minutes) {
            $this->postJson(route('visitors.store'), $payload + ['allowed_minutes' => $minutes])->assertUnprocessable()->assertJsonValidationErrors('allowed_minutes');
        }
        $this->post(route('visitors.store'), $payload + ['allowed_minutes' => 5])->assertSessionHasNoErrors();
        $visitor = Visitor::firstOrFail();
        $this->assertSame(5, $visitor->allowed_minutes);
        $this->assertSame('10:05', $visitor->visit_deadline->format('H:i'));
        $this->travel(5)->minutes();
        $this->assertFalse($visitor->is_overdue);
        $this->post(route('visitors.overdue-action', $visitor), ['overdue_action' => 'Reminder'])->assertSessionHasErrors('overdue_action');
        $this->travel(1)->minutes();
        $this->assertTrue($visitor->is_overdue);
        $this->get(route('visitors.show', $visitor))->assertOk()->assertSee('Overdue follow-up');
        $this->post(route('visitors.overdue-action', $visitor), ['overdue_action' => 'Contacted visitor and requested return to gate.'])->assertSessionHasNoErrors();
        $this->assertSame($security->full_name, $visitor->fresh()->overdue_action_by);
        $this->post(route('visitors.checkout', $visitor))->assertSessionHasNoErrors();
        $this->assertTrue($visitor->fresh()->is_overdue);
    }

    public function test_deadline_crosses_midnight_and_on_time_checkout_stays_on_time(): void
    {
        $visitor = new Visitor(['check_in' => '2026-10-23 23:55:00', 'allowed_minutes' => 15, 'check_out' => '2026-10-24 00:05:00']);
        $this->assertSame('2026-10-24 00:10:00', $visitor->visit_deadline->format('Y-m-d H:i:s'));
        $this->assertFalse($visitor->is_overdue);
        $this->assertNull((new Visitor(['check_in' => now()]))->visit_deadline);
    }
}
