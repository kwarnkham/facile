<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Absence extends Model
{
    use HasFactory, UsesTenantConnection;


    protected static function booted()
    {
        static::created(function () {
            $tenant = app('currentTenant');
            if ($tenant->plan_usage['absence'] > 0)
                $tenant->update(['plan_usage->absence' => $tenant->plan_usage['absence'] - 1]);
        });
    }

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
