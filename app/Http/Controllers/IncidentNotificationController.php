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
        if (str_starts_with($data['key'], 'overdue:')) {
            abort_unless(\App\Support\OverdueVisitFeed::forUser($request->user())->contains('key', $data['key']), 404);
            $user = $request->user();
            $user->visitor_notification_read_keys = collect($user->visitor_notification_read_keys ?? [])->push($data['key'])->unique()->values()->all();
            $user->save();
            return response()->json(['success' => true, 'unread_count' => IncidentNotificationFeed::unreadCountForUser($user)]);
        }
        $request->user()->notifications()->where('type', IncidentUpdatedNotification::class)->findOrFail($data['key'])->markAsRead();

        return response()->json(['success' => true, 'unread_count' => IncidentNotificationFeed::unreadCountForUser($request->user())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->where('type', IncidentUpdatedNotification::class)->update(['read_at' => now()]);
        $request->user()->forceFill(['visitor_notifications_read_at' => now()])->save();

        return response()->json(['success' => true, 'unread_count' => 0]);
    }

    public function clearAll(Request $request): Response
    {
        $request->user()->notifications()->where('type', IncidentUpdatedNotification::class)->delete();
        $request->user()->forceFill(['visitor_notifications_cleared_at' => now(), 'visitor_notifications_read_at' => now()])->save();

        return response()->noContent();
    }
}
