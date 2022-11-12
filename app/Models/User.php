<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $with = ['merchant'];

    public function scopeRole(Builder $query, $roleName)
    {
        $query->whereHas('roles', function (Builder $query) use ($roleName) {
            $query->where('name', $roleName);
        });
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['role'] ?? null,
            fn (Builder $query, $role) => $query->role($role)
        )->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('merchant', function ($query) use ($search) {
                        $query->where('description', 'like', '%' . $search . '%')
                            ->orWhere('address', 'like', '%' . $search . '%');
                    });
            })
        );
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class);
    }

    /**
     * Determine if the user has the given role
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName)
    {
        return $this->roles->contains(fn ($role) => $role->name == $roleName);
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
