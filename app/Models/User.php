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
                $query->where('name', 'like', '%' . $search . '%');
            })
        );
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }


    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains(fn ($role) => $role->name == $roleName);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
