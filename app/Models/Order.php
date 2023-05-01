<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    public function reverseStock()
    {
        $this->aItems->each(function ($aItem) {
            if ($aItem->type == ProductType::STOCKED->value)
                DB::table('a_items')->where('id', $aItem->id)->increment('stock', $aItem->pivot->quantity);
        });
        $this->aItems()->detach();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->using(OrderProduct::class)
            ->withPivot(['quantity', 'price', 'discount', 'name', 'id', 'purchase_price'])->withTimestamps();
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['price', 'quantity', 'discount', 'cost', 'name'])
            ->withTimestamps();
    }

    public function items()
    {
        return $this->belongsToMany(Item::class)
            ->withPivot(['price', 'quantity', 'name'])
            ->withTimestamps();
    }

    public function aItems()
    {
        return $this->belongsToMany(AItem::class)
            ->withPivot(['price', 'quantity', 'name', 'discount', 'purchase_price'])
            ->withTimestamps();
    }

    public function getProductDiscounts()
    {
        return floor((float)$this->products->reduce(function ($carry, $product) {
            return $product->pivot->discount * $product->pivot->quantity + $carry;
        }, 0));
    }

    public function paidAmount()
    {
        return (float)$this->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry, 0);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class)
            ->withPivot([
                'amount', 'number', 'note', 'picture', 'payment_name', 'account_name', 'id'
            ])->withTimestamps();
    }

    public function cancel()
    {
        if (in_array($this->status, [
            OrderStatus::PENDING->value,
            OrderStatus::PARTIALLY_PAID->value,
            OrderStatus::PAID->value,
            OrderStatus::COMPLETED->value
        ])) {
            // if ($order->status == OrderStatus::PAID->value && now()->diffInHours($order->updated_at) >= 24) return Redirect::back()->with('message', 'Cannot cancel a paid order after 24 hours');
            return DB::transaction(function () {
                $this->status = OrderStatus::CANCELED->value;
                $this->updated_by = request()->user()->id;
                $this->save();
                $this->products->each(function ($product) {
                    if ($product->type == ProductType::STOCKED->value) {
                        $product->stock += $product->pivot->quantity;
                        $product->save();
                        $product->pivot->batches->each(function ($batch) {
                            $batch->stock += $batch->pivot->quantity;
                            $batch->save();
                        });
                    }
                });
                return true;
            });
        }
        return false;
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query) => $query->whereIn(
                    'status',
                    explode(',', $filters['status'])
                )
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
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, $search) => $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('phone', 'like',  '%' . $search . '%');
                })
            );
    }
}
