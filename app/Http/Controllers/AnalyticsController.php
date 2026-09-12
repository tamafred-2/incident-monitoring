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

    public function records(Request $request): View
    {
        $data = $request->validate([
            'chart' => ['required', 'in:incidentsTrend,incidentsStatus,incidentsCategory,visitorsTrend,visitorsWeekday,residentsRelation,topHouses'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);
        $chart = $data['chart'];
        $value = $data['value'] ?? '';
        $kind = str_starts_with($chart, 'visitors') ? 'visitors' : ($chart === 'residentsRelation' ? 'residents' : 'incidents');
        $query = match ($kind) {
            'visitors' => Visitor::query()->orderByDesc('check_in'),
            'residents' => Resident::query()->orderBy('full_name'),
            default => Incident::query()->orderByDesc('reported_at'),
        };
        if (!$request->user()->isAdmin()) {
            $query->where('subdivision_id', $request->user()->allowedSubdivisionId());
            if ($kind === 'residents') {
                $query->where('status', 'Active');
            }
        }
        if ($kind !== 'residents') {
            $query->whereBetween($kind === 'visitors' ? 'check_in' : 'reported_at', [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['to'])->endOfDay()]);
        }
        if (in_array($chart, ['incidentsCategory', 'incidentsStatus', 'residentsRelation'], true)) {
            $column = match ($chart) { 'incidentsCategory' => 'category', 'incidentsStatus' => 'status', default => 'relation_to_owner' };
            $fallback = match ($chart) { 'incidentsCategory' => 'Uncategorized', 'incidentsStatus' => 'Unknown', default => 'Unspecified' };
            $query->where(function ($q) use ($column, $value, $fallback) {
                if ($column === 'status') {
                    $q->whereIn($column, \App\Enums\IncidentStatus::valuesForLabel($value));
                } else {
                    $q->where($column, $value);
                }
                if ($value === $fallback) {
                    $q->orWhereNull($column)->orWhere($column, '');
                }
            });
        }
        if ($chart === 'topHouses') {
            $query->where('house_id', $value);
        }
        if ($chart === 'visitorsWeekday') {
            abort_unless(in_array($value, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], true), 422);
            $dates = [];
            for ($day = Carbon::parse($data['from']); $day->lte(Carbon::parse($data['to'])); $day->addDay()) {
                if ($day->format('D') === $value) {
                    $dates[] = $day->toDateString();
                }
            }
            $query->whereIn(DB::raw('DATE(check_in)'), $dates);
        }
        return view('analytics.records', [
            'records' => $query->paginate(20)->withQueryString(),
            'kind' => $kind,
            'selection' => $value,
            'from' => $data['from'],
            'to' => $data['to'],
        ]);
    }

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

        // The From/To range scopes every incident & visitor chart (trend,
        // By Status, By Category). It defaults to month-to-date so the pickers
        // always start filled (e.g. Jul 1 – today).
        $hasRange = filled($request->query('from')) || filled($request->query('to'));
        $trendEnd = filled($request->query('to'))
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();
        $trendStart = filled($request->query('from'))
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        if ($trendStart->greaterThan($trendEnd)) {
            [$trendStart, $trendEnd] = [$trendEnd->copy()->startOfDay(), $trendStart->copy()->endOfDay()];
        }

        $filterStart = $trendStart;
        $filterEnd = $trendEnd;

        return view('analytics.index', [
            'scopeLabel' => $isAdmin ? 'All subdivisions' : ($user->subdivision?->subdivision_name ?? 'Your subdivision'),
            'granularity' => $granularity,
            'granularities' => self::GRANULARITIES,
            'filterFrom' => $trendStart->toDateString(),
            'filterTo' => $trendEnd->toDateString(),
            'hasRange' => $hasRange,
            'rangeLabel' => $trendStart->format('M j, Y') . ' – ' . $trendEnd->format('M j, Y'),
            'incidents' => $this->buildIncidentAnalytics($scope, $granularity, $trendStart, $trendEnd, $filterStart, $filterEnd),
            'visitors' => $this->buildVisitorAnalytics($scope, $granularity, $trendStart, $trendEnd, $filterStart, $filterEnd),
            'community' => $this->buildCommunityAnalytics($scope, $trendStart, $trendEnd),
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
            ->groupBy(fn ($row) => \App\Enums\IncidentStatus::displayLabel($row->status))
            ->map(fn ($rows) => (int) $rows->sum('aggregate'))
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
    private function buildCommunityAnalytics(callable $scope, Carbon $start, Carbon $end): array
    {
        $byRelation = [];

        if (Schema::hasColumn('residents', 'relation_to_owner')) {
            $byRelation = $scope(Resident::query())
                ->when(!auth()->user()->isAdmin(), fn ($q) => $q->where('status', 'Active'))
                ->select('relation_to_owner', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('relation_to_owner')
                ->orderByDesc('aggregate')
                ->get()
                ->mapWithKeys(fn ($row) => [($row->relation_to_owner ?: 'Unspecified') => (int) $row->aggregate])
                ->all();
        }

        $topHouses = $scope(Incident::query())
            ->whereBetween('reported_at', [$start, $end])
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
            'top_house_ids' => $topHouses->pluck('house_id')->all(),
            'avg_residents_per_house' => $totalHouses > 0
                ? round($totalResidents / $totalHouses, 1)
                : 0,
        ];
    }

    private function resolveGranularity(?string $value): string
    {
        $value = is_string($value) ? strtolower($value) : '';

        return in_array($value, self::GRANULARITIES, true) ? $value : 'daily';
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
        $ranges = [];
        $guard = 0;

        while ($cursor->lessThanOrEqualTo($last) && $guard < 2000) {
            $iso = $cursor->format('Y-m-d');
            $labels[$iso] = $cursor->format($format);
            $counts[$iso] = 0;
            $ranges[] = [
                'from' => $cursor->copy()->max($start)->toDateString(),
                'to' => $this->stepUnit($cursor, $unit, 1)->subDay()->min($end)->toDateString(),
            ];
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
            'ranges' => $ranges,
        ];
    }
}
