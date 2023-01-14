<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    public function purchasable()
    {
        return $this->morphTo();
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
            );
    }
}
