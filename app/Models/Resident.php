<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resident extends Model
{
    protected $primaryKey = 'resident_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_id',
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

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'verified_resident_id', 'resident_id');
    }
}
