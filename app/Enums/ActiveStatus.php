<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Generic active/inactive lifecycle status shared by residents and subdivisions.
 */
enum ActiveStatus: string
{
    use HasOptions;

    case Active = 'Active';
    case Inactive = 'Inactive';

    public function label(): string
    {
        return $this->value;
    }
}
