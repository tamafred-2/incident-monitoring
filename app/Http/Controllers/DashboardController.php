<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private ?bool $incidentStatusSchemaIsLegacy = null;

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedId = $user->allowedSubdivisionId();
        $insidePerPage = $this->resolvePerPageChoice(
            $request->query('inside_per_page_custom'),
            $request->query('inside_per_page'),
            10
        );
        $pendingIncidentsPerPage = $this->resolvePerPageChoice(
            $request->query('pending_incidents_per_page_custom'),
            $request->query('pending_incidents_per_page'),
            10
        );
        $isResidentDashboard = $user->isResident();
        $isStaffDashboard = !$isResidentDashboard && $user->role === 'staff';
        $showPendingIncidentList = !$isResidentDashboard && ($isStaffDashboard || $user->isAdmin());

        $scope = fn (Builder $query): Builder => $query->when(
            !$user->isAdmin(),
            fn (Builder $inner) => $inner->where('subdivision_id', $allowedId)
        );

        $summary = $isResidentDashboard ? null : $this->buildSummary($scope);

        $totalSubdivisions = $user->isAdmin()
            ? Subdivision::count()
            : ($allowedId ? 1 : 0);

        $totalIncidents = Incident::when(
            $isResidentDashboard,
            fn ($query) => $query->where('reported_by', $user->user_id),
            fn ($query) => $query->when(
                !$user->isAdmin(),
                fn ($innerQuery) => $innerQuery->where('subdivision_id', $allowedId)
            )
        )->count();

        $totalResidents = $isResidentDashboard
            ? ($user->resident ? 1 : 0)
            : Resident::when(
                !$user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->count();

        $totalHouses = $isResidentDashboard
            ? ($user->resident?->house_id ? 1 : 0)
            : House::when(
                !$user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->count();

        $visitorsToday = $isResidentDashboard
            ? 0
            : Visitor::when(
                !$user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->whereDate('check_in', now()->toDateString())->count();

        $visitorsInside = $isResidentDashboard
            ? 0
            : Visitor::when(
                !$user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->where('status', 'Inside')->count();

        $insideVisitors = $isResidentDashboard
            ? Visitor::query()->whereRaw('1 = 0')->paginate($insidePerPage)
            : Visitor::query()
                ->with('subdivision')
                ->when(
                    !$user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->where('status', 'Inside')
                ->orderByDesc('check_in')
                ->paginate($insidePerPage)
                ->withQueryString();

        $residentOpenIncidents = $isResidentDashboard
            ? Incident::where('reported_by', $user->user_id)
                ->whereIn('status', $this->pendingIncidentStatuses())
                ->count()
            : 0;

        $residentResolvedIncidents = $isResidentDashboard
            ? Incident::where('reported_by', $user->user_id)
                ->whereIn('status', $this->resolvedIncidentStatuses())
                ->count()
            : 0;

        $staffActiveIncidents = $isStaffDashboard
            ? Incident::query()
                ->when(
                    !$user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->whereNotIn('status', $this->resolvedIncidentStatuses())
                ->count()
            : 0;

        $staffPendingIncidents = $isStaffDashboard
            ? Incident::query()
                ->when(
                    !$user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->whereIn('status', $this->pendingIncidentStatuses())
                ->count()
            : 0;

        $dashboardPendingIncidentList = $showPendingIncidentList
            ? Incident::query()
                ->with(['house', 'reporter'])
                ->when(
                    !$user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->whereIn('status', $this->pendingIncidentStatuses())
                ->orderByDesc('reported_at')
                ->orderByDesc('incident_date')
                ->paginate($pendingIncidentsPerPage, ['*'], 'pending_page')
                ->withQueryString()
            : Incident::query()
                ->whereRaw('1 = 0')
                ->paginate($pendingIncidentsPerPage, ['*'], 'pending_page');

        return view('dashboard', compact(
            'isResidentDashboard',
            'isStaffDashboard',
            'showPendingIncidentList',
            'summary',
            'totalSubdivisions',
            'totalIncidents',
            'totalResidents',
            'totalHouses',
            'visitorsToday',
            'visitorsInside',
            'insideVisitors',
            'insidePerPage',
            'pendingIncidentsPerPage',
            'residentOpenIncidents',
            'residentResolvedIncidents',
            'staffActiveIncidents',
            'staffPendingIncidents',
            'dashboardPendingIncidentList',
        ));
    }

    /**
     * Build the summary stat cards shown on the admin/staff dashboard.
     *
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

        $totalResidents = $scope(Resident::query())->count();
        $totalHouses = $scope(House::query())->count();

        return [
            'total_incidents' => $totalIncidents,
            'pending_incidents' => $pendingIncidents,
            'resolved_incidents' => $resolvedIncidents,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_label' => $this->averageResolutionLabel($scope),
            'total_visitors' => $scope(Visitor::query())->count(),
            'visitors_inside' => $scope(Visitor::query())->where('status', 'Inside')->count(),
            'total_residents' => $totalResidents,
            'total_houses' => $totalHouses,
            'avg_residents_per_house' => $totalHouses > 0
                ? round($totalResidents / $totalHouses, 1)
                : 0,
        ];
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

    private function resolvePerPage(mixed $value, int $default = 10): int
    {
        $perPage = (int) $value;

        if ($perPage < 1) {
            return $default;
        }

        return min($perPage, 100);
    }

    private function resolvePerPageChoice(mixed $customValue, mixed $selectedValue, int $default = 10): int
    {
        $custom = (int) $customValue;
        if ($custom > 0) {
            return $this->resolvePerPage($custom, $default);
        }

        return $this->resolvePerPage($selectedValue, $default);
    }

    private function pendingIncidentStatuses(): array
    {
        return $this->usesLegacyIncidentStatusSchema()
            ? ['Reported', 'Investigating']
            : ['Open', 'Under Investigation'];
    }

    private function resolvedIncidentStatuses(): array
    {
        return ['Resolved', 'Closed', 'Completed'];
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
