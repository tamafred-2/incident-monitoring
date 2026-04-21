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

    public static function formatAddress(?string $block, ?string $lot): string
    {
        $block = trim((string) $block);
        $lot = trim((string) $lot);

        return "Block {$block} Lot {$lot}";
    }
}
