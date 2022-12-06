<?php

namespace App\Models;

use App\Traits\HasQR;
use App\Traits\Spaceable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Feature extends Model
{
    use HasFactory, Spaceable, HasQR;

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class)->orderBy('expired_on');
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable')->orderBy('id', 'desc');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot(['quantity', 'price', 'discount', 'fetures'])->withTimestamps();
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('stock', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%')
                    ->orWhere('price', 'like', '%' . $search . '%');
            })
        );

        $query->when($filters['stocked'] ?? null, fn (Builder $query) => $query->where('stock', '>', 0));
    }
}
