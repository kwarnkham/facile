<?php

namespace App\Models;

use App\Traits\Spaceable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Feature extends Model
{
    use HasFactory, Spaceable;

    public static function outOfStock(array $data)
    {
        $features = static::whereIn('id', array_map(fn ($val) => $val['id'], $data))->get(['id', 'stock', 'name']);
        $features->each(function ($feat) use ($data) {
            foreach ($data as $val) {
                if ($feat->id == $val['id']) {
                    if ($feat->stock < $val['quantity']) return ($feat->name . ' is out of stock');
                }
            }
        });
    }

    public static function mapForOrder(array $data)
    {
        $features = static::whereIn('id', array_map(fn ($v) => $v['id'], $data))->with(['item.wholesales'])->get();

        $features = collect($data)->map(function ($feature) use ($features) {
            $features->each(function ($val) use (&$feature) {
                if ($val->id == $feature['id']) {
                    $feature['price'] = $val->price;
                    $feature['batch'] = $val->batches()->where('stock', '>', 0)->first();
                    $feature['batch_id'] = $feature['batch']->id;
                }
            });
            return $feature;
        });
        return $features;
    }

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
