<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedVisit extends Model
{
    protected $fillable = ['visitor_profile_id', 'subdivision_id', 'resident_id', 'recorded_by', 'expected_at', 'purpose', 'status', 'visitor_id'];

    protected function casts(): array
    {
        return ['expected_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(VisitorProfile::class, 'visitor_profile_id', 'profile_id');
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id')->withTrashed();
    }
}
