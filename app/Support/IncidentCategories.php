<?php

namespace App\Support;

/**
 * Incident categories grouped by type. The stored incidents.category value is
 * the specific category; its type is derived, so older records keep working.
 */
class IncidentCategories
{
    public const OTHER = 'Other';

    public const GROUPS = [
        'Infrastructure' => [
            'Streetlight Problem',
            'Road Damage',
            'Drainage / Flooding',
            'Water Supply',
            'Power Outage',
            'Gate / Facility Damage',
            'Property Damage',
        ],
        'Security' => [
            'Security',
            'Theft',
            'Vandalism',
            'Suspicious Activity',
            'Trespassing',
        ],
        'Peace and Order' => [
            'Noise Complaint',
            'Parking',
            'Disturbance',
        ],
        'Safety and Emergency' => [
            'Safety',
            'Medical',
            'Fire',
            'Accident',
        ],
        'Other' => [
            self::OTHER,
        ],
    ];

    /**
     * Every selectable category, flattened.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    /**
     * Type of a stored category; custom or legacy values fall under Other.
     */
    public static function typeOf(?string $category): ?string
    {
        if (! filled($category)) {
            return null;
        }

        foreach (self::GROUPS as $type => $categories) {
            if (in_array($category, $categories, true)) {
                return $type;
            }
        }

        return 'Other';
    }
}
