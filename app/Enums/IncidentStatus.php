<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Canonical incident statuses.
 *
 * NOTE: the incidents.status column is intentionally NOT cast to this enum on
 * the model. Older SQLite databases may still hold legacy values (Reported,
 * Investigating, Ongoing, Done, Completed) that IncidentController maps to the
 * canonical set at runtime; a strict enum cast would throw when reading them.
 * Use this enum for validation, option lists, and canonical comparisons.
 */
enum IncidentStatus: string
{
    use HasOptions;

    case Open = 'Open';
    case UnderInvestigation = 'Under Investigation';
    case Resolved = 'Resolved';
    case Closed = 'Closed';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * Statuses that represent an incident still being handled.
     *
     * @return array<int, self>
     */
    public static function pending(): array
    {
        return [self::Open, self::UnderInvestigation];
    }

    /**
     * Backing values of the pending statuses.
     *
     * @return array<int, string>
     */
    public static function pendingValues(): array
    {
        return array_column(self::pending(), 'value');
    }

    /**
     * Statuses that represent a finished incident.
     *
     * @return array<int, self>
     */
    public static function resolved(): array
    {
        return [self::Resolved, self::Closed];
    }

    /**
     * Backing values of the resolved statuses.
     *
     * @return array<int, string>
     */
    public static function resolvedValues(): array
    {
        return array_column(self::resolved(), 'value');
    }
}
