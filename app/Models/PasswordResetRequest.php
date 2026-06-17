<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * How long a pending request stays valid before it is auto-invalidated.
     */
    public const GRACE_MINUTES = 60;

    protected $fillable = [
        'user_id',
        'email',
        'status',
        'temporary_password',
        'resolved_by',
        'resolved_at',
        'expires_at',
    ];

    protected $hidden = [
        'temporary_password',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Flip any pending request past its grace period to "expired".
     */
    public static function expireStale(): void
    {
        static::query()
            ->where('status', self::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => self::STATUS_EXPIRED]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'user_id');
    }
}
