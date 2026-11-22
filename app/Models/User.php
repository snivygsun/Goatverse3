<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // role / is_owner / vip_until / banned_at are NOT mass-assignable on purpose.
    protected $fillable = ['username', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'vip_until' => 'datetime',
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(UserReward::class);
    }

    public function isVip(): bool
    {
        return $this->vip_until !== null && $this->vip_until->isFuture();
    }

    public function isStaff(): bool
    {
        return $this->is_owner || $this->role !== 'player';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_owner) {
            return true;
        }

        return in_array($permission, config("goatverse.permissions.{$this->role}", []), true);
    }
}
