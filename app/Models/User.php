<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles {
        HasRoles::hasRole as protected spatieHasRole;
    }

    /**
     * Spatie guard backing the role/permission assignments.
     */
    protected string $guard_name = 'web';

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
        'requires_password_change',
        'role',
        'is_active',
        'subdivision_id',
        'resident_id',
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

        static::saved(function (self $user): void {
            $user->syncSpatieRoleFromColumn();
        });
    }

    /**
     * Keep the Spatie role assignment in sync with the canonical `role` column.
     */
    public function syncSpatieRoleFromColumn(): void
    {
        if (blank($this->role) || !Schema::hasTable('roles')) {
            return;
        }

        try {
            $role = SpatieRole::findOrCreate($this->role, $this->guard_name);

            if (!$this->roles()->where($role->getKeyName(), $role->getKey())->exists()) {
                $this->syncRoles([$role->name]);
            }
        } catch (\Throwable $exception) {
            // Never block user persistence on a role-sync hiccup.
            report($exception);
        }
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
            'requires_password_change' => 'boolean',
            'is_active' => 'boolean',
            'visitor_notifications_read_at' => 'datetime',
            'visitor_notifications_cleared_at' => 'datetime',
            'visitor_notification_read_keys' => 'array',
        ];
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Subdivision::class, 'subdivision_id', 'subdivision_id')->withTrashed();
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class, 'resident_id', 'resident_id');
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by', 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResident(): bool
    {
        return $this->role === 'resident';
    }

    /**
     * Role check used throughout the app. Admins implicitly pass any role gate.
     *
     * Accepts role-name strings/arrays (matched against the canonical `role`
     * column) and also defers to Spatie's implementation when called with Role
     * models/collections (e.g. from Spatie's permission-via-role internals).
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!is_string($roles) && !is_array($roles)) {
            return $this->spatieHasRole($roles, $guard);
        }

        $list = is_array($roles) ? $roles : [$roles];

        if (collect($list)->contains(fn ($role) => !is_string($role))) {
            return $this->spatieHasRole($roles, $guard);
        }

        return in_array($this->role, $list, true);
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
