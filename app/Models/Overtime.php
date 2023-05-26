<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Overtime extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime'
    ];

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['from'] ?? null,
            fn (Builder $query, $from) => $query->whereDate(
                'from',
                '>=',
                $from
            )
        );

        $query->when(
            $filters['to'] ?? null,
            fn (Builder $query, $to) => $query->whereDate(
                'from',
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
