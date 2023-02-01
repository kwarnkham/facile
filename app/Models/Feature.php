<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
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
        $message = null;
        $features->each(function ($feat) use ($data, &$message) {
            foreach ($data as $val) {
                if ($feat->id == $val['id']) {
                    if ($feat->stock < $val['quantity']) $message = ($feat->name . ' is out of stock');
                }
            }
        });
        return $message;
    }

    public static function mapForOrder(array $data)
    {
        $features = static::whereIn('id', array_map(fn ($v) => $v['id'], $data))->get();

        $mappedFeatures = collect($data)->map(function ($feature) use ($features) {
            $features->each(function ($val) use (&$feature) {
                if ($val->id == $feature['id']) {
                    $feature['price'] = $val->price;
                    $feature['name'] = $val->name;
                }
            });
            return $feature;
        })->toArray();
        return $mappedFeatures;
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

    public function latestPurchase()
    {
        return $this->morphOne(Purchase::class, 'purchasable')
            ->latestOfMany()
            ->where('status', PurchaseStatus::NORMAL->value);
    }


    public function latestBatch()
    {
        return $this->hasOne(Batch::class)->latestOfMany();
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable')->orderBy('id', 'desc');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)
            ->using(FeatureOrder::class)
            ->withPivot(['quantity', 'price', 'discount', 'name', 'id', 'purchase_price'])
            ->withTimestamps();
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('stock', 'like', '%' . $search . '%')
                    ->orWhere('note', 'like', '%' . $search . '%')
                    ->orWhere('price', 'like', '%' . $search . '%')
                    ->orWhereRelation('item', 'name', 'like', '%' . $search . '%');
            })
        );

        $query->when($filters['stocked'] ?? null, fn (Builder $query) => $query->where('stock', '>', 0));

        $query->when(
            $filters['item'] ?? null,
            fn (Builder $query, $item_id) => $query->whereRelation('item', 'id', '=', $item_id)
        );

        $query->when(
            $filters['limit'] ?? null,
            fn (Builder $query, $limit) => $query->take($limit)
        );
    }
}
