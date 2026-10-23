<?php

namespace Tests\Feature;

use App\Models\{Subdivision, User, Visitor};
use App\Support\OverdueVisitFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueVisitNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_scoped_and_read_or_clear_does_not_repeat(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $home = Subdivision::create(['subdivision_name' => 'Home', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'Other', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        $admin = User::factory()->create(['role' => 'admin']);
        $outsider = User::factory()->create(['role' => 'security', 'subdivision_id' => $other->getKey()]);
        $visitor = Visitor::create(['subdivision_id' => $home->getKey(), 'first_name' => 'Sample', 'surname' => 'Visitor', 'phone' => '09123456789', 'check_in' => now()->subMinutes(6), 'allowed_minutes' => 5, 'status' => 'Inside']);
        $key = 'overdue:'.$visitor->getKey();
        $this->assertCount(1, OverdueVisitFeed::forUser($admin));
        $this->assertCount(0, OverdueVisitFeed::forUser($outsider));
        $this->actingAs($admin)->getJson(route('admin.visitor-notifications.index'))->assertOk()->assertJsonFragment(['key' => $key]);
        $this->actingAs($guard)->getJson(route('incident-notifications.index'))->assertOk()->assertJsonFragment(['key' => $key, 'type' => 'visitor_overdue']);
        $this->postJson(route('incident-notifications.read-one'), ['key' => $key])->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertFalse(OverdueVisitFeed::forUser($guard->fresh())->first()['is_unread']);
        $this->deleteJson(route('incident-notifications.clear-all'))->assertNoContent();
        $this->assertCount(0, OverdueVisitFeed::forUser($guard->fresh()));
        $this->assertTrue(OverdueVisitFeed::forUser($admin)->first()['is_unread']);
    }

    public function test_not_due_or_on_time_checkout_does_not_notify(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Home', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin']);
        $visitor = Visitor::create(['subdivision_id' => $home->getKey(), 'first_name' => 'Sample', 'surname' => 'Visitor', 'phone' => '09123456789', 'check_in' => now(), 'allowed_minutes' => 5, 'status' => 'Inside']);
        $this->assertCount(0, OverdueVisitFeed::forUser($admin));
        $visitor->update(['check_out' => now()->addMinutes(4), 'status' => 'Checked Out']);
        $this->travel(10)->minutes();
        $this->assertCount(0, OverdueVisitFeed::forUser($admin));
    }
}
