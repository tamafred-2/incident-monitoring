<?php

namespace App\Models;

use App\Enums\IncidentStatus;
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
        'house_id',
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
        'verified_by_staff_id',
        'verified_on_site_at',
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
            'verified_on_site_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class, 'house_id', 'house_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id')->withTrashed();
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id')->withTrashed();
    }

    public function verifiedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_staff_id', 'user_id')->withTrashed();
    }

    public function proofPhotos(): HasMany
    {
        return $this->hasMany(IncidentPhoto::class, 'incident_id', 'incident_id')
            ->orderBy('sort_order')
            ->orderBy('incident_photo_id');
    }

    public function isResolvedOrDone(): bool
    {
        // 'Done' is a legacy value still possible on older databases; the
        // canonical resolved statuses come from the IncidentStatus enum.
        return in_array($this->status, [...IncidentStatus::resolvedValues(), 'Done'], true);
    }
}
