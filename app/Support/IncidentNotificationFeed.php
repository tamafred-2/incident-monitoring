<?php

namespace App\Support;

use App\Models\User;
use App\Models\Incident;
use App\Notifications\IncidentUpdatedNotification;
use Illuminate\Support\Collection;

class IncidentNotificationFeed
{
    public static function recentForUser(User $user): Collection
    {
        $notifications = $user->notifications()->where('type', IncidentUpdatedNotification::class)
            ->latest()->limit(8)->get();
        $incidents = Incident::withTrashed()->with('reporter')
            ->whereIn('incident_id', $notifications->pluck('data.incident_id')->filter())->get()->keyBy('incident_id');

        return $notifications->map(fn ($notification): array => [
                'key' => $notification->id,
                'type' => 'incident_updated',
                'incident_id' => $notification->data['incident_id'] ?? null,
                'title' => (($notification->data['title'] ?? '') === 'New Incident Reported'
                    || str_ends_with($notification->data['title'] ?? '', ' created a new incident'))
                    ? ($incidents->get($notification->data['incident_id'] ?? null)?->reporter?->first_name ?: 'A user').' created a new incident'
                    : ($notification->data['title'] ?? 'Incident update'),
                'message' => $notification->data['message'] ?? '',
                'detail_url' => $notification->data['url'] ?? route('incidents.index'),
                'time_label' => $notification->created_at->format('M j, Y h:i A'),
                'relative_time' => $notification->created_at->diffForHumans(),
                'is_unread' => $notification->read_at === null,
                'sort_at' => $notification->created_at->timestamp,
            ])->concat(OverdueVisitFeed::forUser($user))->sortByDesc('sort_at')->take(8)->values();
    }

    public static function unreadCountForUser(User $user): int
    {
        return $user->unreadNotifications()->where('type', IncidentUpdatedNotification::class)->count()
            + OverdueVisitFeed::forUser($user)->where('is_unread', true)->count();
    }
}
