<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    protected $primaryKey = 'house_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_id',
        'street',
        'block',
        'lot',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class, 'house_id', 'house_id');
    }

    public function getDisplayAddressAttribute(): string
    {
        return self::formatAddress($this->block, $this->lot);
    }

    public function setBlockAttribute(?string $value): void
    {
        $this->attributes['block'] = self::normalizeBlock($value);
    }

    public function setLotAttribute(?string $value): void
    {
        $this->attributes['lot'] = self::normalizeLot($value);
    }

    public static function normalizeBlock(?string $value): string
    {
        return self::normalizeAddressComponent($value, '/^(?:BLOCK|BLK)\b[\s\-.:#]*/');
    }

    public static function normalizeLot(?string $value): string
    {
        return self::normalizeAddressComponent($value, '/^(?:LOT|LT)\b[\s\-.:#]*/');
    }

    public static function formatAddress(?string $block, ?string $lot): string
    {
        $block = self::normalizeBlock($block);
        $lot = self::normalizeLot($lot);

        return "Block {$block} Lot {$lot}";
    }

    private static function normalizeAddressComponent(?string $value, string $prefixPattern): string
    {
        $normalized = strtoupper((string) $value);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        $normalized = trim($normalized);

        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace($prefixPattern, '', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if (preg_match('/^\d+$/', $normalized) === 1) {
            $normalized = ltrim($normalized, '0');
            $normalized = $normalized === '' ? '0' : $normalized;
        }

        return $normalized;
    }
}
