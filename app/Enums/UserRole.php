<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum UserRole: string
{
    use HasOptions;

    case Admin = 'admin';
    case Security = 'security';
    case Staff = 'staff';
    case Resident = 'resident';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Roles that can be assigned through the user-management screens.
     * Residents are provisioned via the resident workflow, not the user form.
     *
     * @return array<int, self>
     */
    public static function manageable(): array
    {
        return [self::Admin, self::Security, self::Staff];
    }

    /**
     * Backing values of the manageable roles.
     *
     * @return array<int, string>
     */
    public static function manageableValues(): array
    {
        return array_column(self::manageable(), 'value');
    }
}
