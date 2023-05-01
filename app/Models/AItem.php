<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AItem extends Model
{
    use HasFactory;

    public function recordPurchase(array $attributes): Purchase
    {
        $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'purchases') : null;

        $data = [
            'price' => $attributes['purchase_price'],
            'quantity' => $attributes['stock'],
            'name' => $this->name,
        ];

        if ($picture) {
            $data['picture'] = $picture;
        }

        return $this->purchases()->create($data);
    }

    public static function checkStock(array $data)
    {
        $aItems = static::whereIn('id', array_map(fn ($val) => $val['id'], $data))->get(['id', 'stock', 'name']);
        $aItems->each(function ($item) use ($data) {
            foreach ($data as $val) {
                if ($item->id == $val['id'])
                    abort_if($item->stock < $val['quantity'] && $item->type == ProductType::STOCKED->value, ResponseStatus::BAD_REQUEST->value, $item->name . ' is out of stock');
            }
        });
    }

    public static function mapForOrder(array $data)
    {
        $aItems = static::with(['latestPurchase'])->whereIn('id', array_map(fn ($v) => $v['id'], $data))->get();

        return collect($data)->map(function ($item) use ($aItems) {
            $aItems->each(function ($val) use (&$item) {
                if ($val->id == $item['id']) {
                    $item['price'] = $val->price;
                    $item['name'] = $val->name;
                    $item['purchase_price'] = $val->type == ProductType::STOCKED->value ? $val->latestPurchase->price : $val->price;
                }
            });
            return $item;
        })->toArray();
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable')->orderBy('id', 'desc');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class)
            ->withPivot(['price', 'quantity', 'name', 'discount', 'purchase_price'])
            ->withTimestamps();
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }


    public function latestPurchase()
    {
        return $this->morphOne(Purchase::class, 'purchasable')
            ->latestOfMany()
            ->where('status', PurchaseStatus::NORMAL->value);
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

        $query->when(
            $filters['minStock'] ?? null,
            fn (Builder $query, $minStock) => $query->where('stock', '>=', $minStock)
        );

        $query->when(
            $filters['limit'] ?? null,
            fn (Builder $query, $limit) => $query->take($limit)
        );
    }
}
