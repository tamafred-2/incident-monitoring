<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for backed enums that need to expose their values and
 * build <select> option lists. The using enum must define a label() method.
 */
trait HasOptions
{
    /**
     * The backing values of every case.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * A value => label map, handy for building dropdown options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $case): array {
                $carry[$case->value] = $case->label();

                return $carry;
            },
            []
        );
    }
}
