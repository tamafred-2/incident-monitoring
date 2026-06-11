<?php

namespace App\Http\Controllers;

use App\Support\VisitorActivityFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminVisitorNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => VisitorActivityFeed::recentForUser($user)->values(),
            'unread_count' => VisitorActivityFeed::unreadCountForUser($user),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->visitor_notifications_read_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'unread_count' => VisitorActivityFeed::unreadCountForUser($user),
        ]);
    }

    public function readOne(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        $user = $request->user();
        $readKeys = collect($user->visitor_notification_read_keys ?? [])
            ->push($data['key'])
            ->unique()
            ->values()
            ->all();

        $user->visitor_notification_read_keys = $readKeys;
        $user->save();

        return response()->json([
            'success' => true,
            'unread_count' => VisitorActivityFeed::unreadCountForUser($user),
        ]);
    }

    public function clearAll(Request $request): Response
    {
        $user = $request->user();
        $now = now();
        $user->visitor_notifications_read_at = $now;
        $user->visitor_notifications_cleared_at = $now;
        $user->save();

        return response()->noContent();
    }
}