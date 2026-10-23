<?php

namespace Tests\Feature;

use App\Models\{Subdivision, User, Visitor, VisitorProfile};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorProfileSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sorting_and_status_filters_apply_to_profiles(): void
    {
        $home = Subdivision::create(['subdivision_name' => 'Sample Estate', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        $base = ['subdivision_id' => $home->getKey(), 'surname' => 'Sample', 'phone' => '09123456789'];
        $ana = VisitorProfile::create($base + ['first_name' => 'Ana']);
        $zoe = VisitorProfile::create($base + ['first_name' => 'Zoe']);
        $ana->forceFill(['created_at' => now()->subDays(2)])->save();
        Visitor::create($base + ['visitor_profile_id' => $zoe->getKey(), 'first_name' => 'Zoe', 'status' => 'Inside', 'check_in' => now()]);
        $this->actingAs($guard);
        foreach (['name_asc' => $ana, 'name_desc' => $zoe, 'newest' => $zoe, 'oldest' => $ana, 'invalid' => $ana] as $sort => $first) {
            $this->get(route('visitor-profiles.index', ['sort' => $sort]))->assertOk()
                ->assertViewHas('profiles', fn ($profiles) => $profiles->first()->getKey() === $first->getKey());
        }
        $this->get(route('visitor-profiles.index'))->assertOk()->assertSee('Created At')->assertSee('Sort Name descending')->assertSee('Sort Created At descending')->assertDontSee('id="profile-sort"', false);
        foreach (['inside' => $zoe, 'outside' => $ana] as $status => $expected) {
            $this->get(route('visitor-profiles.index', ['status' => $status, 'q' => 'Sample', 'sort' => 'newest']))->assertOk()
                ->assertViewHas('profiles', fn ($profiles) => $profiles->count() === 1 && $profiles->first()->getKey() === $expected->getKey());
        }
    }
}
