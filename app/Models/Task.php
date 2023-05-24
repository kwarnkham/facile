<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where('name', 'like', '%' . $search . '%')
        );
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }
}
