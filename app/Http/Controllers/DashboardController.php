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
        $insidePerPage = (int) $request->query('inside_per_page', 5);

        if (!in_array($insidePerPage, [5, 10], true)) {
            $insidePerPage = 5;
        }

        $totalSubdivisions = $user->isAdmin()
            ? Subdivision::count()
            : ($allowedId ? 1 : 0);

        $totalIncidents = Incident::when(
            !$user->isAdmin(),
            fn ($query) => $query->where('subdivision_id', $allowedId)
        )->count();

        $totalResidents = Resident::when(
            !$user->isAdmin(),
            fn ($query) => $query->where('subdivision_id', $allowedId)
        )->count();

        $totalHouses = House::when(
            !$user->isAdmin(),
            fn ($query) => $query->where('subdivision_id', $allowedId)
        )->count();

        $visitorsToday = Visitor::when(
            !$user->isAdmin(),
            fn ($query) => $query->where('subdivision_id', $allowedId)
        )->whereDate('check_in', now()->toDateString())->count();

        $visitorsInside = Visitor::when(
            !$user->isAdmin(),
            fn ($query) => $query->where('subdivision_id', $allowedId)
        )->where('status', 'Inside')->count();

        $insideVisitors = Visitor::query()
            ->with('subdivision')
            ->when(
                !$user->isAdmin(),
                fn ($query) => $query->where('subdivision_id', $allowedId)
            )
            ->where('status', 'Inside')
            ->orderByDesc('check_in')
            ->paginate($insidePerPage)
            ->withQueryString();

        $breakdown = $user->isAdmin()
            ? Subdivision::withCount([
                'incidents',
                'residents',
                'houses',
                'visitors as visitors_inside_count' => fn ($query) => $query->where('status', 'Inside'),
                'houses as occupied_houses_count' => fn ($query) => $query->whereHas('residents'),
            ])->orderBy('subdivision_name')->get()
            : collect();

        return view('dashboard', compact(
            'totalSubdivisions',
            'totalIncidents',
            'totalResidents',
            'totalHouses',
            'visitorsToday',
            'visitorsInside',
            'insideVisitors',
            'insidePerPage',
            'breakdown'
        ));
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
}
