<?php

namespace App\Support;

use App\Models\{User, Visitor};
use Illuminate\Support\Collection;

class OverdueVisitFeed
{
    public static function forUser(User $user, int $limit = 40): Collection
    {
        if (! $user->isAdmin() && ! $user->isSecurity()) {
            return collect();
        }
        return Visitor::query()->whereNotNull('allowed_minutes')->whereNotNull('check_in')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('subdivision_id', $user->allowedSubdivisionId()))
            ->where('check_in', '<', now())->orderByDesc('check_in')->get()
            ->filter(fn (Visitor $visitor) => $visitor->is_overdue)
            ->map(function (Visitor $visitor) use ($user): array {
                $deadline = $visitor->visit_deadline;
                $key = 'overdue:'.$visitor->getKey();
                return [
                    'key' => $key, 'type' => 'visitor_overdue', 'visitor_id' => $visitor->getKey(),
                    'title' => $visitor->full_name.' exceeded the visit time limit',
                    'message' => 'Allowed '.$visitor->allowed_minutes.' minutes; due '.$deadline->format('g:i A').'. '.($visitor->check_out ? 'Visitor has checked out. Review any follow-up.' : 'Please contact the visitor and record any action taken.'),
                    'detail_url' => route('visitors.show', $visitor),
                    'occurred_at' => $deadline->toIso8601String(), 'time_label' => $deadline->format('M j, Y h:i A'),
                    'relative_time' => $deadline->diffForHumans(), 'sort_at' => $deadline->timestamp,
                    'is_unread' => ! in_array($key, $user->visitor_notification_read_keys ?? [], true)
                        && (! $user->visitor_notifications_read_at || $deadline->gt($user->visitor_notifications_read_at)),
                ];
            })->filter(fn ($event) => ! $user->visitor_notifications_cleared_at || $event['sort_at'] > $user->visitor_notifications_cleared_at->timestamp)
            ->sortByDesc('sort_at')->take($limit)->values();
    }
}
