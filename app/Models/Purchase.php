<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Purchase extends Model
{
    use HasFactory, UsesTenantConnection;

    public function purchasable()
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function () {
            $tenant = app('currentTenant');
            if ($tenant->plan_usage['purchase'] > 0)
                $tenant->update(['plan_usage->purchase' => $tenant->plan_usage['purchase'] - 1]);
        });
    }

    public function picture(): Attribute
    {
        return Attribute::make(
            fn ($value) => $value ? Storage::url(
                config('app')['name'] . '/purchases/' . config('app')['env'] . '/' . $value
            ) : $value
        );
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('price', 'like', '%' . $search . '%')
                    ->orWhere('quantity', 'like', '%' . $search . '%')
                    ->orWhereHas('purchasable', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
            })
        );

        $query->when(
            $filters['from'] ?? null,
            fn (Builder $query, $from) => $query->whereDate(
                'updated_at',
                '>=',
                $from
            )
        );

        $query->when(
            $filters['to'] ?? null,
            fn (Builder $query, $to) => $query->whereDate(
                'updated_at',
                '<=',
                $to
            )
        );

        $query->when(
            $filters['status'] ?? null,
            fn (Builder $query, $status) => $query->where('status', $status)
        );

        $query->when(
            $filters['type'] ?? null,
            fn (Builder $query, $type) => $query->where('purchasable_type', $type)
        );

        $query->when(
            array_key_exists('group', $filters),
            fn (Builder $query) => $query->where('group', $filters['group'])
        );
    }
}
