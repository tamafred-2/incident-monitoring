<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $primaryKey = 'user_id';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'surname',
        'first_name',
        'middle_name',
        'extension',
        'email',
        'password',
        'role',
        'subdivision_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->isDirty(['first_name', 'middle_name', 'surname', 'extension']) || empty($user->full_name)) {
                $user->full_name = self::formatFullName(
                    $user->first_name,
                    $user->surname,
                    $user->middle_name,
                    $user->extension
                );
            }
        });
    }

    public static function formatFullName(
        ?string $firstName,
        ?string $surname,
        ?string $middleName = null,
        ?string $extension = null
    ): string {
        $segments = array_filter([
            self::cleanNamePart($firstName),
            self::cleanNamePart($middleName),
            self::cleanNamePart($surname),
            self::cleanNamePart($extension),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return implode(' ', $segments);
    }

    protected static function cleanNamePart(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'visitor_notifications_read_at' => 'datetime',
            'visitor_notifications_cleared_at' => 'datetime',
            'visitor_notification_read_keys' => 'array',
        ];
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by', 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string|array $roles): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function allowedSubdivisionId(): ?int
    {
        if ($this->isAdmin()) {
            return null;
        }

        return $this->subdivision_id ? (int) $this->subdivision_id : null;
    }

    public function canAccessSubdivision(int|string|null $subdivisionId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$subdivisionId || !$this->subdivision_id) {
            return false;
        }

        return (int) $this->subdivision_id === (int) $subdivisionId;
    }
}
