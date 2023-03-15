<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Traits\Spaceable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, Spaceable;

    public static function outOfStock(array $data)
    {
        $products = static::whereIn('id', array_map(fn ($val) => $val['id'], $data))->get(['id', 'stock', 'name']);
        $message = null;
        $products->each(function ($product) use ($data, &$message) {
            foreach ($data as $val) {
                if ($product->id == $val['id']) {
                    if ($product->stock < $val['quantity']) $message = ($product->name . ' is out of stock');
                }
            }
        });
        return $message;
    }

    public static function mapForOrder(array $data)
    {
        $products = static::with(['latestPurchase'])->whereIn('id', array_map(fn ($v) => $v['id'], $data))->get();

        $mappedProducts = collect($data)->map(function ($product) use ($products) {
            $products->each(function ($val) use (&$product) {
                if ($val->id == $product['id']) {
                    $product['price'] = $val->price;
                    $product['name'] = $val->name;
                    $product['purchase_price'] = $val->latestPurchase->price;
                }
            });
            return $product;
        })->toArray();
        return $mappedProducts;
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
            ->using(OrderProduct::class)
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
