<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    public function features()
    {
        return $this->belongsToMany(Feature::class)
            ->using(FeatureOrder::class)
            ->withPivot(['quantity', 'price', 'discount', 'name', 'id'])->withTimestamps();
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

    public function getFeatureDiscounts()
    {
        return floor((float)$this->features->reduce(function ($carry, $feature) {
            return $feature->pivot->discount * $feature->pivot->quantity + $carry;
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
            );
    }
}
