<?php

namespace Tests\Feature;

use App\Models\{Subdivision, User, Visitor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorCheckoutSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_sorting_preserves_scope_filters_and_pagination(): void
    {
        $this->freezeTime();
        $home = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'South', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $home->getKey()]);
        $visits = [];
        foreach (['Ana', 'Zoe', 'Mia'] as $i => $name) {
            $visits[] = Visitor::create([
                'subdivision_id' => $home->getKey(), 'first_name' => $name, 'surname' => 'Cruz',
                'check_in' => now()->subHours(3 - $i), 'status' => 'Inside',
            ]);
        }
        foreach ([['status' => 'Checked Out'], ['subdivision_id' => $other->getKey()]] as $overrides) {
            $visits[0]->replicate()->fill($overrides)->save();
        }
        $this->actingAs($guard);
        foreach (['name_asc' => [0, 2, 1], 'name_desc' => [1, 2, 0], 'newest' => [2, 1, 0], 'oldest' => [0, 1, 2], 'invalid' => [2, 1, 0]] as $sort => $order) {
            $expected = array_map(fn ($i) => $visits[$i]->getKey(), $order);
            $this->get(route('visitors.index', ['sort' => $sort, 'tab' => 'check-out']))->assertOk()
                ->assertViewHas('insideVisitors', fn ($rows) => $rows->modelKeys() === $expected);
        }
        $this->get(route('visitors.index', ['sort' => 'name_desc', 'q' => 'Mia']))->assertOk()
            ->assertViewHas('insideVisitors', fn ($rows) => $rows->modelKeys() === [$visits[2]->getKey()]);
        for ($i = 0; $i < 11; $i++) $visits[0]->replicate()->save();
        $first = $this->get(route('visitors.index', ['sort' => 'name_asc']))->assertOk()->viewData('insideVisitors');
        $this->assertStringContainsString('sort=name_asc', $first->nextPageUrl());
        $second = $this->get($first->nextPageUrl())->assertOk()->viewData('insideVisitors');
        $this->assertEmpty(array_intersect($first->modelKeys(), $second->modelKeys()));
    }
}

