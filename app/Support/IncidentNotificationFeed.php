<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\IncidentUpdatedNotification;
use Illuminate\Support\Collection;

class IncidentNotificationFeed
{
    public static function recentForUser(User $user): Collection
    {
        return $user->notifications()->where('type', IncidentUpdatedNotification::class)
            ->latest()->limit(8)->get()->map(fn ($notification): array => [
                'key' => $notification->id,
                'type' => 'incident_updated',
                'incident_id' => $notification->data['incident_id'] ?? null,
                'title' => $notification->data['title'] ?? 'Incident update',
                'message' => $notification->data['message'] ?? '',
                'detail_url' => $notification->data['url'] ?? route('incidents.index'),
                'time_label' => $notification->created_at->format('M j, Y h:i A'),
                'relative_time' => $notification->created_at->diffForHumans(),
                'is_unread' => $notification->read_at === null,
            ]);
    }

    public static function unreadCountForUser(User $user): int
    {
        return $user->unreadNotifications()->where('type', IncidentUpdatedNotification::class)->count();
    }
}
