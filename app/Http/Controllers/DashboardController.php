<?php

namespace App\Http\Controllers;

use App\Enums\IncidentStatus;
use App\Enums\VisitorStatus;
use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private ?bool $incidentStatusSchemaIsLegacy = null;

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedId = $user->allowedSubdivisionId();
        $isResidentDashboard = $user->isResident();
        $isStaffDashboard = ! $isResidentDashboard && $user->isStaff();
        $showPendingIncidentList = ! $isResidentDashboard && ($isStaffDashboard || $user->isAdmin());

        $scope = fn (Builder $query): Builder => $query->when(
            ! $user->isAdmin(),
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
                ! $user->isAdmin(),
                fn ($innerQuery) => $innerQuery->where('subdivision_id', $allowedId)
            )
        )->count();

        $totalResidents = $isResidentDashboard
            ? ($user->resident ? 1 : 0)
            : Resident::when(
                ! $user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->count();

        $totalHouses = $isResidentDashboard
            ? ($user->resident?->house_id ? 1 : 0)
            : House::when(
                ! $user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->count();

        $visitorsToday = $isResidentDashboard
            ? 0
            : Visitor::when(
                ! $user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->whereDate('check_in', now()->toDateString())->count();

        $visitorsInside = $isResidentDashboard
            ? 0
            : Visitor::when(
                ! $user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )->where('status', VisitorStatus::Inside)->count();

        $insideVisitors = $this->dashboardRecords($request, 'visitors');

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
                    ! $user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->whereNotIn('status', $this->resolvedIncidentStatuses())
                ->count()
            : 0;

        $staffPendingIncidents = $isStaffDashboard
            ? Incident::query()
                ->when(
                    ! $user->isAdmin(),
                    fn ($query) => $query->where('subdivision_id', $allowedId)
                )
                ->whereIn('status', $this->pendingIncidentStatuses())
                ->count()
            : 0;

        $dashboardPendingIncidentList = $this->dashboardRecords($request, 'incidents');

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
            'residentOpenIncidents',
            'residentResolvedIncidents',
            'staffActiveIncidents',
            'staffPendingIncidents',
            'dashboardPendingIncidentList',
        ));
    }

    public function records(Request $request, string $table): JsonResponse
    {
        abort_unless(in_array($table, ['incidents', 'visitors'], true), 404);
        abort_unless($this->canViewRecords($request->user(), $table), 403);

        $records = $this->dashboardRecords($request, $table);

        return response()->json([
            'html' => view("dashboard.partials.{$table}-rows", compact('records'))->render(),
            'next_cursor' => $records->nextCursor()?->encode(),
            'count' => $records->count(),
        ]);
    }

    private function canViewRecords(User $user, string $table): bool
    {
        return $table === 'incidents'
            ? $user->isAdmin() || $user->isStaff()
            : $user->isAdmin() || $user->isSecurity();
    }

    private function dashboardRecords(Request $request, string $table): CursorPaginator
    {
        $user = $request->user();
        $isIncident = $table === 'incidents';
        $query = $isIncident ? Incident::query() : Visitor::query();
        $query->when(! $this->canViewRecords($user, $table), fn ($q) => $q->whereRaw('1 = 0'))
            ->when(! $user->isAdmin(), fn ($q) => $q->where('subdivision_id', $user->allowedSubdivisionId()));

        if ($isIncident) {
            $query->whereIn('status', $this->pendingIncidentStatuses())
                ->select('incidents.*')
                ->selectRaw('COALESCE(reported_at, incident_date) AS dashboard_sort_at')
                ->orderByDesc('dashboard_sort_at');
        } else {
            $query->where('status', VisitorStatus::Inside)->orderByDesc('check_in');
        }

        // Break timestamp ties with a unique key so inserts and checkouts do not shift later batches.
        return $query->orderByDesc($isIncident ? 'incident_id' : 'visitor_id')
            ->cursorPaginate(5, ['*'], $isIncident ? 'incident_cursor' : 'visitor_cursor');
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

        $totalResidents = $scope(Resident::query())->count();
        $totalHouses = $scope(House::query())->count();

        return [
            'total_incidents' => $totalIncidents,
            'pending_incidents' => $pendingIncidents,
            'resolved_incidents' => $resolvedIncidents,
            'total_visitors' => $scope(Visitor::query())->count(),
            'visitors_inside' => $scope(Visitor::query())->where('status', VisitorStatus::Inside)->count(),
            'total_residents' => $totalResidents,
            'total_houses' => $totalHouses,
            'avg_residents_per_house' => $totalHouses > 0
                ? round($totalResidents / $totalHouses, 1)
                : 0,
        ];
    }

    private function pendingIncidentStatuses(): array
    {
        return $this->usesLegacyIncidentStatusSchema()
            ? ['Reported', 'Investigating']
            : IncidentStatus::pendingValues();
    }

    private function resolvedIncidentStatuses(): array
    {
        // 'Completed' is a legacy value still possible on older databases; the
        // canonical resolved statuses come from the IncidentStatus enum.
        return [...IncidentStatus::resolvedValues(), 'Completed'];
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
