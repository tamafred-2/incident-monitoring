<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /** Selectable trend granularities. */
    private const GRANULARITIES = ['daily', 'weekly', 'monthly', 'yearly'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedId = $user->allowedSubdivisionId();
        $isAdmin = $user->isAdmin();

        $scope = fn (Builder $query): Builder => $query->when(
            !$isAdmin,
            fn (Builder $inner) => $inner->where('subdivision_id', $allowedId)
        );

        $granularity = $this->resolveGranularity($request->query('granularity'));

        // A custom From/To range scopes every incident & visitor chart (trend,
        // By Status, By Category). Without it the trend uses a sensible default
        // window for the granularity and the breakdowns stay all-time.
        $hasRange = filled($request->query('from')) || filled($request->query('to'));
        $trendEnd = filled($request->query('to'))
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();
        $trendStart = filled($request->query('from'))
            ? Carbon::parse($request->query('from'))->startOfDay()
            : $this->defaultStart($trendEnd, $granularity);

        if ($trendStart->greaterThan($trendEnd)) {
            [$trendStart, $trendEnd] = [$trendEnd->copy()->startOfDay(), $trendStart->copy()->endOfDay()];
        }

        $filterStart = $hasRange ? $trendStart : null;
        $filterEnd = $hasRange ? $trendEnd : null;

        return view('analytics.index', [
            'scopeLabel' => $isAdmin ? 'All subdivisions' : ($user->subdivision?->subdivision_name ?? 'Your subdivision'),
            'granularity' => $granularity,
            'granularities' => self::GRANULARITIES,
            'filterFrom' => (string) $request->query('from', ''),
            'filterTo' => (string) $request->query('to', ''),
            'hasRange' => $hasRange,
            'rangeLabel' => $hasRange
                ? $trendStart->format('M j, Y') . ' – ' . $trendEnd->format('M j, Y')
                : 'All time',
            'incidents' => $this->buildIncidentAnalytics($scope, $granularity, $trendStart, $trendEnd, $filterStart, $filterEnd),
            'visitors' => $this->buildVisitorAnalytics($scope, $granularity, $trendStart, $trendEnd, $filterStart, $filterEnd),
            'community' => $this->buildCommunityAnalytics($scope),
        ]);
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildIncidentAnalytics(
        callable $scope,
        string $granularity,
        Carbon $trendStart,
        Carbon $trendEnd,
        ?Carbon $filterStart,
        ?Carbon $filterEnd
    ): array {
        $trend = $this->bucketRange(
            $scope(Incident::query())
                ->whereNotNull('reported_at')
                ->whereBetween('reported_at', [$trendStart, $trendEnd])
                ->pluck('reported_at'),
            $granularity,
            $trendStart,
            $trendEnd
        );

        $byCategory = $scope(Incident::query())
            ->when($filterStart, fn (Builder $q) => $q->whereBetween('reported_at', [$filterStart, $filterEnd]))
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('category')
            ->orderByDesc('aggregate')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->category ?: 'Uncategorized') => (int) $row->aggregate])
            ->all();

        $byStatus = $scope(Incident::query())
            ->when($filterStart, fn (Builder $q) => $q->whereBetween('reported_at', [$filterStart, $filterEnd]))
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->status ?: 'Unknown') => (int) $row->aggregate])
            ->all();

        return [
            'trend' => $trend,
            'category_labels' => array_keys($byCategory),
            'category_values' => array_values($byCategory),
            'status_labels' => array_keys($byStatus),
            'status_values' => array_values($byStatus),
        ];
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildVisitorAnalytics(
        callable $scope,
        string $granularity,
        Carbon $trendStart,
        Carbon $trendEnd,
        ?Carbon $filterStart,
        ?Carbon $filterEnd
    ): array {
        $trendCheckIns = $scope(Visitor::query())
            ->whereNotNull('check_in')
            ->whereBetween('check_in', [$trendStart, $trendEnd])
            ->pluck('check_in');

        $trend = $this->bucketRange($trendCheckIns, $granularity, $trendStart, $trendEnd);

        // Weekday breakdown follows the same rule: ranged when a custom range is
        // set, otherwise all-time.
        $weekdayCheckIns = $filterStart
            ? $trendCheckIns
            : $scope(Visitor::query())->whereNotNull('check_in')->pluck('check_in');

        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $byDayOfWeek = array_fill_keys($dayLabels, 0);

        foreach ($weekdayCheckIns as $checkIn) {
            $day = Carbon::parse($checkIn)->format('D');
            if (array_key_exists($day, $byDayOfWeek)) {
                $byDayOfWeek[$day]++;
            }
        }

        return [
            'trend' => $trend,
            'weekday_labels' => $dayLabels,
            'weekday_values' => array_values($byDayOfWeek),
        ];
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildCommunityAnalytics(callable $scope): array
    {
        $byRelation = [];

        if (Schema::hasColumn('residents', 'relation_to_owner')) {
            $byRelation = $scope(Resident::query())
                ->select('relation_to_owner', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('relation_to_owner')
                ->orderByDesc('aggregate')
                ->get()
                ->mapWithKeys(fn ($row) => [($row->relation_to_owner ?: 'Unspecified') => (int) $row->aggregate])
                ->all();
        }

        $topHouses = $scope(Incident::query())
            ->select('house_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('house_id')
            ->groupBy('house_id')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->with('house')
            ->get();

        $houseLabels = [];
        $houseValues = [];

        foreach ($topHouses as $row) {
            $houseLabels[] = $row->house?->display_address ?? ('House #' . $row->house_id);
            $houseValues[] = (int) $row->aggregate;
        }

        $totalResidents = $scope(Resident::query())->count();
        $totalHouses = $scope(House::query())->count();

        return [
            'relation_labels' => array_keys($byRelation),
            'relation_values' => array_values($byRelation),
            'top_house_labels' => $houseLabels,
            'top_house_values' => $houseValues,
            'avg_residents_per_house' => $totalHouses > 0
                ? round($totalResidents / $totalHouses, 1)
                : 0,
        ];
    }

    private function resolveGranularity(?string $value): string
    {
        $value = is_string($value) ? strtolower($value) : '';

        return in_array($value, self::GRANULARITIES, true) ? $value : 'monthly';
    }

    /**
     * Default window start for a granularity when no custom From date is given.
     */
    private function defaultStart(Carbon $end, string $granularity): Carbon
    {
        return match ($granularity) {
            'daily' => $end->copy()->startOfDay()->subDays(29),       // 30 days
            'weekly' => $end->copy()->startOfWeek()->subWeeks(11),    // 12 weeks
            'yearly' => $end->copy()->startOfYear()->subYears(4),     // 5 years
            default => $end->copy()->startOfMonth()->subMonths(11),   // 12 months
        };
    }

    private function granularityUnit(string $granularity): string
    {
        return match ($granularity) {
            'daily' => 'day',
            'weekly' => 'week',
            'yearly' => 'year',
            default => 'month',
        };
    }

    private function granularityFormat(string $granularity): string
    {
        return match ($granularity) {
            'daily', 'weekly' => 'M j',
            'yearly' => 'Y',
            default => 'M Y',
        };
    }

    private function floorUnit(Carbon $date, string $unit): Carbon
    {
        return match ($unit) {
            'day' => $date->copy()->startOfDay(),
            'week' => $date->copy()->startOfWeek(),
            'year' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function stepUnit(Carbon $date, string $unit, int $amount): Carbon
    {
        return match ($unit) {
            'day' => $date->copy()->addDays($amount),
            'week' => $date->copy()->addWeeks($amount),
            'year' => $date->copy()->addYears($amount),
            default => $date->copy()->addMonths($amount),
        };
    }

    /**
     * Bucket datetimes across an explicit [$start, $end] window at the given
     * granularity. Buckets are keyed by ISO date internally so display labels
     * can never collide; the value array is one total per period.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $dates
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function bucketRange($dates, string $granularity, Carbon $start, Carbon $end): array
    {
        $unit = $this->granularityUnit($granularity);
        $format = $this->granularityFormat($granularity);

        $cursor = $this->floorUnit($start, $unit);
        $last = $this->floorUnit($end, $unit);

        $labels = [];
        $counts = [];
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($last) && $guard < 2000) {
            $iso = $cursor->format('Y-m-d');
            $labels[$iso] = $cursor->format($format);
            $counts[$iso] = 0;
            $cursor = $this->stepUnit($cursor, $unit, 1);
            $guard++;
        }

        foreach ($dates as $date) {
            if ($date === null) {
                continue;
            }

            $iso = $this->floorUnit(Carbon::parse($date), $unit)->format('Y-m-d');
            if (array_key_exists($iso, $counts)) {
                $counts[$iso]++;
            }
        }

        return [
            'labels' => array_values($labels),
            'values' => array_values($counts),
        ];
    }
}
