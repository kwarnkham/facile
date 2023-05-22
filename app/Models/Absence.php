<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['from'] ?? null,
            fn (Builder $query, $from) => $query->whereDate(
                'date',
                '>=',
                $from
            )
        );

        $query->when(
            $filters['to'] ?? null,
            fn (Builder $query, $to) => $query->whereDate(
                'date',
                '<=',
                $to
            )
        );

        $query->when(
            $filters['user_id'] ?? null,
            fn (Builder $query, $user_id) => $query->where(
                'user_id',
                '=',
                $user_id
            )
        );
    }
}
