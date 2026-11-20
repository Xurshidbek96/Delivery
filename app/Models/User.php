<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property UserRole $role
 * @property bool $is_active
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function courierProfile(): HasOne
    {
        return $this->hasOne(CourierProfile::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function clientOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function courierOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function scopeRole(Builder $query, UserRole $role): Builder
    {
        return $query->where('role', $role->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function activeCourierOrdersCount(): int
    {
        return $this->courierOrders()
            ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, OrderStatus::courierActive()))
            ->count();
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name) ?: '?', 0, 1));
    }
}
