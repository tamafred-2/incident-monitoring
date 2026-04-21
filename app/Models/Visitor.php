<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'visitor_id';

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_id',
        'full_name',
        'surname',
        'first_name',
        'middle_initials',
        'extension',
        'phone',
        'plate_number',
        'passenger_count',
        'id_photo_path',
        'purpose',
        'host_employee',
        'house_address_or_unit',
        'check_in',
        'check_out',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $visitor): void {
            if ($visitor->isDirty(['first_name', 'middle_initials', 'surname', 'extension']) || empty($visitor->full_name)) {
                $visitor->full_name = self::formatFullName(
                    $visitor->first_name,
                    $visitor->middle_initials,
                    $visitor->surname,
                    $visitor->extension
                );
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public static function formatFullName(
        ?string $firstName,
        ?string $middleInitials,
        ?string $surname,
        ?string $extension = null
    ): string {
        $segments = array_filter([
            self::cleanNamePart($firstName),
            self::cleanNamePart($middleInitials),
            self::cleanNamePart($surname),
            self::cleanNamePart($extension),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return implode(' ', $segments);
    }

    protected static function cleanNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
