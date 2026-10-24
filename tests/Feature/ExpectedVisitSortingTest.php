<?php

namespace Tests\Feature;

use App\Models\{ExpectedVisit, House, Resident, Subdivision, User, VisitorProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpectedVisitSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_visits_sort_by_name_and_creation_time_and_preserve_scope(): void
    {
        $this->freezeTime();
        $home = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'South', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        $house = House::create(['subdivision_id' => $home->getKey(), 'block' => '1', 'lot' => '1', 'street' => 'Main']);
        $resident = Resident::create(['subdivision_id' => $home->getKey(), 'house_id' => $house->getKey(), 'full_name' => 'Jose Reyes', 'relation_to_owner' => 'Owner', 'status' => 'Active']);
        $visits = [];
        foreach (['Ana', 'Zoe', 'Mia'] as $i => $name) {
            $profile = VisitorProfile::create(['subdivision_id' => $home->getKey(), 'first_name' => $name, 'surname' => 'Cruz']);
            $visit = ExpectedVisit::create([
                'subdivision_id' => $home->getKey(), 'visitor_profile_id' => $profile->getKey(),
                'resident_id' => $resident->getKey(), 'recorded_by' => $guard->getKey(),
                'expected_at' => now()->addDays(3 - $i), 'status' => 'Expected',
            ]);
            $visit->forceFill(['created_at' => now()->subDays(3 - $i)])->save();
            $visits[] = $visit;
        }
        foreach ([['status' => 'Cancelled'], ['subdivision_id' => $other->getKey()]] as $overrides) {
            $copy = $visits[0]->replicate();
            $copy->fill($overrides)->save();
        }
        $this->actingAs($guard)->withoutVite();
        foreach (['name_asc' => [0, 2, 1], 'name_desc' => [1, 2, 0], 'newest' => [2, 1, 0], 'oldest' => [0, 1, 2], 'invalid' => [0, 2, 1]] as $sort => $order) {
            $expected = array_map(fn ($i) => $visits[$i]->getKey(), $order);
            $this->get(route('expected-visits.index', ['sort' => $sort]))->assertOk()
                ->assertViewHas('visits', fn ($rows) => $rows->pluck('id')->all() === $expected);
        }
        $this->get(route('expected-visits.index'))->assertOk()
            ->assertSee('Sort Visitor descending')->assertSee('Sort Created At descending');

        for ($i = 0; $i < 14; $i++) $visits[0]->replicate()->save();
        $response = $this->get(route('expected-visits.index', ['sort' => 'newest']))->assertOk();
        $this->assertStringContainsString('sort=newest', $response->viewData('visits')->nextPageUrl());
        $firstIds = $response->viewData('visits')->pluck('id')->all();
        $second = $this->get(route('expected-visits.index', ['sort' => 'newest', 'page' => 2]))->assertOk();
        $this->assertEmpty(array_intersect($firstIds, $second->viewData('visits')->pluck('id')->all()));
    }
}
