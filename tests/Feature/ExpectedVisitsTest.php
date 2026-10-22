<?php

namespace Tests\Feature;

use App\Models\{ExpectedVisit, House, Resident, Subdivision, User, Visitor, VisitorProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpectedVisitsTest extends TestCase
{
    use RefreshDatabase;

    private function gate(): array
    {
        $this->freezeTime();
        Storage::fake('public');
        $subdivision = Subdivision::create(['subdivision_name' => 'North Gate', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'street' => 'Main', 'block' => '1', 'lot' => '1']);
        $resident = Resident::create(['subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(), 'full_name' => 'Ana Cruz', 'phone' => '09123456789', 'relation_to_owner' => 'Owner', 'status' => 'Active']);
        $this->actingAs($guard)->post(route('visitor-profiles.store'), [
            'first_name' => 'Ralph', 'surname' => 'Cruz', 'phone' => '09123456789', 'advance_registration' => 1,
        ])->assertSessionHasNoErrors();
        return [$guard, $resident, VisitorProfile::sole()];
    }

    public function test_expected_visit_does_not_check_in_until_arrival_and_cannot_be_used_twice(): void
    {
        [$guard, $resident, $profile] = $this->gate();
        $this->assertNull($profile->id_photo_path);
        $this->get(route('expected-visits.create', $profile))->assertRedirect(route('visitor-profiles.show', ['profile' => $profile, 'schedule' => 1]));
        $this->get(route('visitor-profiles.show', ['profile' => $profile, 'schedule' => 1]))->assertOk()->assertSee('Ana Cruz')->assertSee('schedule-expected-visit');
        $this->post(route('expected-visits.store', $profile), [
            'resident_id' => $resident->getKey(), 'expected_at' => now()->addHours(2)->format('Y-m-d\TH:i'), 'purpose' => 'Family visit',
        ])->assertSessionHasNoErrors()->assertRedirect(route('expected-visits.index'));
        $expected = ExpectedVisit::sole();
        $this->assertSame($guard->getKey(), $expected->recorded_by);
        $this->assertDatabaseCount('visitors', 0);
        $this->get(route('expected-visits.index'))->assertOk()->assertSee('Ralph Cruz')->assertSee('Family visit');
        $this->get(route('visitors.index', ['expected_visit' => $expected->getKey()]))->assertOk()
            ->assertViewHas('selectedProfile', fn ($selected) => $selected['id'] === $profile->getKey())
            ->assertSee('name="expected_visit_id"', false);
        $payload = ['expected_visit_id' => $expected->getKey(), 'owner_permission' => 1, 'approving_owner_id' => $resident->getKey()];
        $this->post(route('visitors.store'), $payload)->assertSessionHasErrors('id_photo');
        $this->assertSame('Expected', $expected->fresh()->status);
        $this->travel(2)->hours();
        $this->post(route('visitors.store'), $payload + ['id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg')])->assertSessionHasNoErrors();
        $visit = Visitor::sole();
        $this->assertSame(now()->toDateTimeString(), $visit->check_in->toDateTimeString());
        $this->assertSame('Arrived', $expected->fresh()->status);
        $this->assertSame($visit->getKey(), $expected->fresh()->visitor_id);
        $this->post(route('visitors.store'), $payload)->assertSessionHasErrors('expected_visit_id');
        $this->assertDatabaseCount('visitors', 1);
        $this->get(route('expected-visits.index'))->assertOk()->assertDontSee('Ralph Cruz');
    }

    public function test_schedule_validation_cancellation_and_subdivision_access(): void
    {
        [$guard, $resident, $profile] = $this->gate();
        $payload = ['resident_id' => $resident->getKey(), 'expected_at' => now()->addHour()->toDateTimeString()];
        $this->post(route('expected-visits.store', $profile), array_replace($payload, ['expected_at' => now()->subMinute()->toDateTimeString()]))->assertSessionHasErrors('expected_at');
        $this->post(route('expected-visits.store', $profile), array_replace($payload, ['resident_id' => 999]))->assertSessionHasErrors('resident_id');
        $this->post(route('expected-visits.store', $profile), $payload)->assertSessionHasNoErrors();
        $this->post(route('expected-visits.store', $profile), $payload)->assertSessionHasErrors('expected_at');
        $expected = ExpectedVisit::sole();
        $otherSubdivision = Subdivision::create(['subdivision_name' => 'Other', 'status' => 'Active']);
        $outsider = User::factory()->create(['role' => 'security', 'subdivision_id' => $otherSubdivision->getKey()]);
        $this->actingAs($outsider)->get(route('expected-visits.index'))->assertOk()->assertDontSee('Ralph Cruz');
        $this->get(route('expected-visits.create', $profile))->assertForbidden();
        $this->post(route('expected-visits.store', $profile), $payload)->assertForbidden();
        $this->post(route('expected-visits.cancel', $expected))->assertForbidden();
        $this->post(route('visitors.store'), ['expected_visit_id' => $expected->getKey()])->assertForbidden();
        $this->actingAs($guard)->post(route('expected-visits.cancel', $expected))->assertSessionHas('success');
        $this->assertSame('Cancelled', $expected->fresh()->status);
        $this->post(route('visitors.store'), ['expected_visit_id' => $expected->getKey()])->assertSessionHasErrors('expected_visit_id');
        $this->assertDatabaseCount('visitors', 0);
        $this->post(route('expected-visits.store', $profile), $payload)->assertSessionHasNoErrors();
        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $resident->subdivision_id]);
        $this->actingAs($staff)->get(route('expected-visits.index'))->assertOk()->assertDontSee('Check In on Arrival');
        $this->post(route('expected-visits.store', $profile), $payload)->assertRedirect(route('dashboard'));
    }
}
