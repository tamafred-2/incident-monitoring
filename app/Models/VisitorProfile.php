<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorProfile extends Model
{
    protected $primaryKey = 'profile_id';

    protected $fillable = ['subdivision_id', 'surname', 'first_name', 'middle_initials', 'extension', 'full_name', 'phone', 'id_photo_path'];

    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            if ($profile->isDirty(['first_name', 'middle_initials', 'surname', 'extension']) || ! $profile->full_name) {
                $name = Visitor::formatFullName($profile->first_name, $profile->middle_initials, $profile->surname, $profile->extension);
                if ($name !== '') {
                    $profile->full_name = $name;
                }
            }
        });
    }

    public function getVisitorNumberAttribute(): string
    {
        return 'V-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visitor::class, 'visitor_profile_id', 'profile_id');
    }
}
