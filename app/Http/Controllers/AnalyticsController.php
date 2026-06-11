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
    private const MONTHS_WINDOW = 12;

    private ?bool $incidentStatusSchemaIsLegacy = null;

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedId = $user->allowedSubdivisionId();
        $isAdmin = $user->isAdmin();

        $scope = fn (Builder $query): Builder => $query->when(
            !$isAdmin,
            fn (Builder $inner) => $inner->where('subdivision_id', $allowedId)
        );

        return view('analytics.index', [
            'scopeLabel' => $isAdmin ? 'All subdivisions' : ($user->subdivision?->subdivision_name ?? 'Your subdivision'),
            'summary' => $this->buildSummary($scope),
            'incidents' => $this->buildIncidentAnalytics($scope),
            'visitors' => $this->buildVisitorAnalytics($scope),
            'community' => $this->buildCommunityAnalytics($scope),
        ]);
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildSummary(callable $scope): array
    {
        $totalIncidents = $scope(Incident::query())->count();
        $resolvedIncidents = $scope(Incident::query())
            ->whereIn('status', $this->resolvedIncidentStatuses())
            ->count();
        $pendingIncidents = $scope(Incident::query())
            ->whereIn('status', $this->pendingIncidentStatuses())
            ->count();

        $resolutionRate = $totalIncidents > 0
            ? round(($resolvedIncidents / $totalIncidents) * 100)
            : 0;

        return [
            'total_incidents' => $totalIncidents,
            'pending_incidents' => $pendingIncidents,
            'resolved_incidents' => $resolvedIncidents,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_label' => $this->averageResolutionLabel($scope),
            'total_visitors' => $scope(Visitor::query())->count(),
            'visitors_inside' => $scope(Visitor::query())->where('status', 'Inside')->count(),
            'total_residents' => $scope(Resident::query())->count(),
            'total_houses' => $scope(House::query())->count(),
        ];
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildIncidentAnalytics(callable $scope): array
    {
        $monthly = $this->monthlyCounts(
            $scope(Incident::query())
                ->where('reported_at', '>=', $this->windowStart())
                ->pluck('reported_at')
        );

        $byCategory = $scope(Incident::query())
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('category')
            ->orderByDesc('aggregate')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->category ?: 'Uncategorized') => (int) $row->aggregate])
            ->all();

        $byStatus = $scope(Incident::query())
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->get()
            ->mapWithKeys(fn ($row) => [($row->status ?: 'Unknown') => (int) $row->aggregate])
            ->all();

        return [
            'monthly_labels' => array_keys($monthly),
            'monthly_values' => array_values($monthly),
            'category_labels' => array_keys($byCategory),
            'category_values' => array_values($byCategory),
            'status_labels' => array_keys($byStatus),
            'status_values' => array_values($byStatus),
        ];
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function buildVisitorAnalytics(callable $scope): array
    {
        $checkIns = $scope(Visitor::query())
            ->whereNotNull('check_in')
            ->where('check_in', '>=', $this->windowStart())
            ->pluck('check_in');

        $monthly = $this->monthlyCounts($checkIns);

        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $byDayOfWeek = array_fill_keys($dayLabels, 0);

        foreach ($checkIns as $checkIn) {
            $day = Carbon::parse($checkIn)->format('D');
            if (array_key_exists($day, $byDayOfWeek)) {
                $byDayOfWeek[$day]++;
            }
        }

        return [
            'monthly_labels' => array_keys($monthly),
            'monthly_values' => array_values($monthly),
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

    /**
     * Group a collection of datetimes into the last N calendar months.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $dates
     * @return array<string, int>
     */
    private function monthlyCounts($dates): array
    {
        $buckets = [];
        $cursor = now()->startOfMonth()->subMonths(self::MONTHS_WINDOW - 1);

        for ($i = 0; $i < self::MONTHS_WINDOW; $i++) {
            $buckets[$cursor->format('M Y')] = 0;
            $cursor->addMonth();
        }

        foreach ($dates as $date) {
            if ($date === null) {
                continue;
            }

            $key = Carbon::parse($date)->format('M Y');
            if (array_key_exists($key, $buckets)) {
                $buckets[$key]++;
            }
        }

        return $buckets;
    }

    /**
     * @param  callable(Builder): Builder  $scope
     */
    private function averageResolutionLabel(callable $scope): string
    {
        $resolved = $scope(Incident::query())
            ->whereNotNull('reported_at')
            ->whereNotNull('resolved_at')
            ->whereIn('status', $this->resolvedIncidentStatuses())
            ->get(['reported_at', 'resolved_at']);

        if ($resolved->isEmpty()) {
            return 'N/A';
        }

        $totalHours = 0.0;
        $count = 0;

        foreach ($resolved as $incident) {
            $hours = $incident->reported_at->diffInHours($incident->resolved_at, false);
            if ($hours < 0) {
                continue;
            }

            $totalHours += $hours;
            $count++;
        }

        if ($count === 0) {
            return 'N/A';
        }

        $avgHours = $totalHours / $count;

        if ($avgHours < 24) {
            return round($avgHours) . 'h';
        }

        return round($avgHours / 24, 1) . 'd';
    }

    private function windowStart(): Carbon
    {
        return now()->startOfMonth()->subMonths(self::MONTHS_WINDOW - 1);
    }

    private function pendingIncidentStatuses(): array
    {
        return $this->usesLegacyIncidentStatusSchema()
            ? ['Reported', 'Investigating']
            : ['Open', 'Under Investigation'];
    }

    private function resolvedIncidentStatuses(): array
    {
        return ['Resolved', 'Closed', 'Completed', 'Done'];
    }

    private function usesLegacyIncidentStatusSchema(): bool
    {
        if ($this->incidentStatusSchemaIsLegacy !== null) {
            return $this->incidentStatusSchemaIsLegacy;
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            return $this->incidentStatusSchemaIsLegacy = false;
        }

        $tableSql = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'incidents')
            ->value('sql');

        return $this->incidentStatusSchemaIsLegacy = is_string($tableSql)
            && str_contains($tableSql, "'Reported'")
            && str_contains($tableSql, "'Investigating'");
    }
}