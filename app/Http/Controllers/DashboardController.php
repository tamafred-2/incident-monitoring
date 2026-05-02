<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
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
            ? ['Reported', 'Investigating', 'Ongoing']
            : ['Open', 'Under Investigation'];
    }

    private function resolvedIncidentStatuses(): array
    {
        return $this->usesLegacyIncidentStatusSchema()
            ? ['Resolved', 'Closed', 'Completed']
            : ['Resolved', 'Closed', 'Completed'];
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
