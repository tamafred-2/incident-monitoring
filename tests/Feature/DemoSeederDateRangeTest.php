<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeederDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_activity_covers_january_through_seed_time_in_order(): void
    {
        $this->travelTo(Carbon::parse('2026-09-12 14:30:00', config('app.timezone')));
        $this->seed(DatabaseSeeder::class);

        foreach ([[Incident::class, 'reported_at'], [Visitor::class, 'check_in'], [VisitorRequest::class, 'requested_at']] as [$model, $column]) {
            $this->assertFalse($model::where($column, '<', '2026-01-01 00:00:00')->exists());
            $this->assertFalse($model::where($column, '>', now())->exists());
            $this->assertTrue($model::whereBetween($column, ['2026-01-01 00:00:00', '2026-01-07 23:59:59'])->exists());
            for ($month = 1; $month <= 9; $month++) {
                $this->assertTrue($model::whereMonth($column, $month)->exists(), "Missing month {$month} for {$model}");
            }
        }

        $this->assertFalse(Incident::whereColumn('reported_at', '<', 'incident_date')->exists());
        $this->assertFalse(Incident::whereColumn('resolved_at', '<', 'reported_at')->orWhere('resolved_at', '>', now())->exists());
        $this->assertFalse(Visitor::whereColumn('check_out', '<', 'check_in')->orWhere('check_out', '>', now())->exists());
        $this->assertFalse(VisitorRequest::whereColumn('responded_at', '<', 'requested_at')->orWhere('responded_at', '>', now())->exists());
    }
}
