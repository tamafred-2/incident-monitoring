<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorRequest extends Model
{
    protected $primaryKey = 'request_id';

    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'visitor_id',
        'resident_id',
        'subdivision_id',
        'visitor_name',
        'surname',
        'first_name',
        'middle_initials',
        'extension',
        'phone',
        'plate_number',
        'id_photo_path',
        'house_address_or_unit',
        'purpose',
        'status',
        'requested_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'visitor_id');
    }
}
