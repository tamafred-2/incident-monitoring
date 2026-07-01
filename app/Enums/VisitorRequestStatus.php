<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitorRequestStatus: string
{
    use HasOptions;

    case Pending = 'Pending';
    case Approved = 'Approved';
    case Declined = 'Declined';

    public function label(): string
    {
        return $this->value;
    }
}
