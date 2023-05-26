<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Role extends Model
{
    use HasFactory, UsesTenantConnection;

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }
}
