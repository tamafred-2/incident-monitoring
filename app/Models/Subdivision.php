<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subdivision extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'subdivision_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'subdivision_name',
        'address',
        'contact_person',
        'contact_number',
        'email',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'subdivision_id', 'subdivision_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'subdivision_id', 'subdivision_id');
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class, 'subdivision_id', 'subdivision_id');
    }

    public function houses(): HasMany
    {
        return $this->hasMany(House::class, 'subdivision_id', 'subdivision_id');
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class, 'subdivision_id', 'subdivision_id');
    }
}
