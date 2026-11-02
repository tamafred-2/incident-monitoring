<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardDevice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'user_id',
        'token_hash',
        'device_name',
        'ip_address',
        'status',
        'approved_by',
        'approved_at',
        'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }
}
