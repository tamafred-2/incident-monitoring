<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentPhoto extends Model
{
    protected $primaryKey = 'incident_photo_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'incident_id',
        'photo_path',
        'sort_order',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id', 'incident_id');
    }
}
