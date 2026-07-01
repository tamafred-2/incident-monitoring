<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitorStatus: string
{
    use HasOptions;

    case Inside = 'Inside';
    case CheckedOut = 'Checked Out';

    public function label(): string
    {
        return $this->value;
    }
}
