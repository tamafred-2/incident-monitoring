<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'incident_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_id',
        'title',
        'description',
        'category',
        'location',
        'incident_date',
        'reported_at',
        'resolved_at',
        'status',
        'proof_photo_path',
        'reported_by',
        'assigned_to',
        'verified_resident_id',
        'verification_method',
        'verified_at',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    protected function casts(): array
    {
        return [
            'incident_date' => 'datetime',
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id')->withTrashed();
    }

    public function verifiedResident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'verified_resident_id', 'resident_id');
    }

    public function proofPhotos(): HasMany
    {
        return $this->hasMany(IncidentPhoto::class, 'incident_id', 'incident_id')
            ->orderBy('sort_order')
            ->orderBy('incident_photo_id');
    }
}
