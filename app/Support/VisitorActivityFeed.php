<?php

namespace App\Support;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Support\Collection;

class VisitorActivityFeed
{
    public static function recentForUser(User $user, int $limit = 8): Collection
    {
        return self::baseEvents(max($limit * 4, 40))
            ->filter(fn (array $activity): bool => self::isVisibleToUser($activity, $user))
            ->take($limit)
            ->values()
            ->map(function (array $activity) use ($user): array {
                unset($activity['sort_at']);

                $activity['is_unread'] = self::isUnreadForUser($activity, $user);

                return $activity;
            })
            ->values();
    }

    public static function unreadCountForUser(User $user, int $limit = 8): int
    {
        return self::recentForUser($user, $limit)
            ->where('is_unread', true)
            ->count();
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    private static function baseEvents(int $sourceLimit): Collection
    {
        $checkIns = Visitor::query()
            ->with('subdivision')
            ->whereNotNull('check_in')
            ->orderByDesc('check_in')
            ->limit($sourceLimit)
            ->get()
            ->map(fn (Visitor $visitor): array => self::formatActivity($visitor, 'checked_in', $visitor->check_in));

        $checkOuts = Visitor::query()
            ->with('subdivision')
            ->whereNotNull('check_out')
            ->orderByDesc('check_out')
            ->limit($sourceLimit)
            ->get()
            ->map(fn (Visitor $visitor): array => self::formatActivity($visitor, 'checked_out', $visitor->check_out));

        return $checkIns
            ->concat($checkOuts)
            ->sortByDesc('sort_at')
            ->values();
    }

    /**
     * @param  array<string, int|string|null>  $activity
     */
    private static function isVisibleToUser(array $activity, User $user): bool
    {
        if (!$user->visitor_notifications_cleared_at) {
            return true;
        }

        return isset($activity['sort_at']) && (int) $activity['sort_at'] > $user->visitor_notifications_cleared_at->timestamp;
    }

    /**
     * @param  array<string, int|string|null>  $activity
     */
    private static function isUnreadForUser(array $activity, User $user): bool
    {
        $readKeys = collect($user->visitor_notification_read_keys ?? []);

        if (isset($activity['key']) && $readKeys->contains($activity['key'])) {
            return false;
        }

        if (!$user->visitor_notifications_read_at) {
            return true;
        }

        return isset($activity['sort_at']) && (int) $activity['sort_at'] > $user->visitor_notifications_read_at->timestamp;
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function formatActivity(Visitor $visitor, string $type, $occurredAt): array
    {
        $label = $type === 'checked_out' ? 'checked out' : 'checked in';
        $subdivisionName = $visitor->subdivision->subdivision_name ?? 'Unknown subdivision';

        return [
            'key' => implode(':', [
                $visitor->visitor_id,
                $type,
                $occurredAt?->timestamp,
            ]),
            'type' => $type,
            'visitor_id' => $visitor->visitor_id,
            'visitor_name' => $visitor->full_name,
            'subdivision_name' => $subdivisionName,
            'message' => "{$visitor->full_name} {$label} at {$subdivisionName}.",
            'detail_url' => route('visitors.show', $visitor),
            'occurred_at' => $occurredAt?->toIso8601String(),
            'time_label' => $occurredAt?->format('M j, Y h:i A'),
            'relative_time' => $occurredAt?->diffForHumans(),
            'sort_at' => $occurredAt?->timestamp,
        ];
    }
}
