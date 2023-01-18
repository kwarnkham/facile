<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Purchase extends Model
{
    use HasFactory;

    public function purchasable()
    {
        return $this->morphTo();
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
        $query
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('price', 'like', '%' . $search . '%')
                        ->orWhere('quantity', 'like', '%' . $search . '%')
                        ->orWhereHas('purchasable', function ($query) use ($search) {
                            $query->where('name', 'like', '%' . $search . '%');
                        });
                })
            )
            ->when(
                $filters['from'] ?? null,
                fn (Builder $query, $from) => $query->whereDate(
                    'updated_at',
                    '>=',
                    $from
                )
            )
            ->when(
                $filters['to'] ?? null,
                fn (Builder $query, $to) => $query->whereDate(
                    'updated_at',
                    '<=',
                    $to
                )
            );
    }
}
