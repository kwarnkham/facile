<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Duty extends Model
{
    use HasFactory;

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where('name', 'like', '%' . $search . '%')
        );

        $query->when(
            $filters['task_id'] ?? null,
            fn (Builder $query, $task_id) => $query->where('task_id', '=', $task_id)
        );
    }
}
