<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\Visitor;
use App\Support\VisitorActivityFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedId = $user->allowedSubdivisionId();
        $insidePerPage = $this->resolvePerPageChoice(
            $request->query('inside_per_page_custom'),
            $request->query('inside_per_page'),
            10
        );
        $isResidentDashboard = $user->isResident();

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
                ->whereIn('status', ['Open', 'Under Investigation'])
                ->count()
            : 0;

        $residentResolvedIncidents = $isResidentDashboard
            ? Incident::where('reported_by', $user->user_id)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->count()
            : 0;

        return view('dashboard', compact(
            'isResidentDashboard',
            'totalSubdivisions',
            'totalIncidents',
            'totalResidents',
            'totalHouses',
            'visitorsToday',
            'visitorsInside',
            'insideVisitors',
            'insidePerPage',
            'residentOpenIncidents',
            'residentResolvedIncidents'
        ));
    }

    public function notificationsPage(Request $request): View
    {
        $user = $request->user();
        $notifications = $user->notifications()->orderByDesc('created_at')->paginate(15);

        $user->unreadNotifications->markAsRead();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return response()->json([
            'notifications' => VisitorActivityFeed::recentForUser($request->user())->values(),
            'unread_count' => VisitorActivityFeed::unreadCountForUser($request->user()),
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user?->isAdmin(), 403);

        $user->forceFill([
            'visitor_notifications_read_at' => now(),
            'visitor_notification_read_keys' => [],
        ])->save();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    public function markNotificationRead(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user?->isAdmin(), 403);

        $data = $request->validate([
            'key' => ['required', 'string', 'max:255'],
        ]);

        $readKeys = collect($user->visitor_notification_read_keys ?? [])
            ->push($data['key'])
            ->unique()
            ->take(-100)
            ->values()
            ->all();

        $user->forceFill([
            'visitor_notification_read_keys' => $readKeys,
        ])->save();

        return response()->json([
            'success' => true,
            'unread_count' => VisitorActivityFeed::unreadCountForUser($user->fresh()),
        ]);
    }

    public function clearNotifications(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user?->isAdmin(), 403);

        $user->forceFill([
            'visitor_notifications_read_at' => now(),
            'visitor_notifications_cleared_at' => now(),
            'visitor_notification_read_keys' => [],
        ])->save();

        return response()->noContent();
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
}
