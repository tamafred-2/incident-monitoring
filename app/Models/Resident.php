<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resident extends Model
{
    private const KNOWN_EXTENSIONS = ['JR', 'JR.', 'SR', 'SR.', 'II', 'III', 'IV', 'V'];

    protected $primaryKey = 'resident_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_id',
        'house_id',
        'full_name',
        'phone',
        'email',
        'address_or_unit',
        'resident_code',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class, 'house_id', 'house_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'verified_resident_id', 'resident_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'resident_id', 'resident_id');
    }

    public function getDisplayAddressAttribute(): ?string
    {
        return $this->house?->display_address ?: $this->address_or_unit;
    }

    public function getNamePartsAttribute(): array
    {
        $fullName = trim((string) $this->full_name);

        if ($fullName === '') {
            return [
                'surname' => null,
                'first_name' => null,
                'middle_name' => null,
                'extension' => null,
            ];
        }

        $segments = preg_split('/\s+/', $fullName) ?: [];
        $extension = null;

        if ($segments !== []) {
            $lastSegment = strtoupper(rtrim((string) end($segments), '.'));

            if (in_array($lastSegment, array_map(static fn (string $value) => rtrim($value, '.'), self::KNOWN_EXTENSIONS), true)) {
                $extension = array_pop($segments);
            }
        }

        $firstName = $segments[0] ?? null;
        $surname = count($segments) > 1 ? array_pop($segments) : null;
        $middleName = count($segments) > 1 ? implode(' ', array_slice($segments, 1)) : null;

        return [
            'surname' => $surname,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'extension' => $extension,
        ];
    }
}
