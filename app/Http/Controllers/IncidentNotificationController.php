<?php

namespace App\Http\Controllers;

use App\Notifications\IncidentUpdatedNotification;
use App\Support\IncidentNotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IncidentNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'notifications' => IncidentNotificationFeed::recentForUser($request->user()),
            'unread_count' => IncidentNotificationFeed::unreadCountForUser($request->user()),
        ]);
    }

    public function readOne(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string']]);
        $request->user()->notifications()->where('type', IncidentUpdatedNotification::class)->findOrFail($data['key'])->markAsRead();

        return response()->json(['success' => true, 'unread_count' => IncidentNotificationFeed::unreadCountForUser($request->user())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->where('type', IncidentUpdatedNotification::class)->update(['read_at' => now()]);

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    public function clearAll(Request $request): Response
    {
        $request->user()->notifications()->where('type', IncidentUpdatedNotification::class)->delete();

        return response()->noContent();
    }
}
