<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'phone_verified_at',
        'email_verified_at',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function adminUser(): HasOne
    {
        return $this->hasOne(AdminUser::class);
    }

    public function journeyPassengers(): HasMany
    {
        return $this->hasMany(JourneyPassenger::class, 'passenger_id');
    }

    public function journeys(): BelongsToMany
    {
        return $this->belongsToMany(Journey::class, 'journey_passengers', 'passenger_id', 'journey_id')
            ->withPivot('connected_at', 'disconnected_at', 'status')
            ->withTimestamps();
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'user_id');
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isTourist(): bool
    {
        return $this->role === 'tourist';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'support'], true)
            || ($this->adminUser !== null && $this->adminUser->status === 'active');
    }

    public function hasAdminRole(string $role): bool
    {
        return $this->isAdmin() && ($this->adminUser?->hasRole($role) ?? false);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAdminRole('super_admin');
    }

    public function hasPermission(string $permission): bool
    {
        return app(\App\Services\PermissionService::class)->hasPermission($this, $permission);
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin() && $this->status === 'active';
        }

        return false;
    }
}
