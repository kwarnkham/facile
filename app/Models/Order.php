<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    public function features()
    {
        return $this->belongsToMany(Feature::class)
            ->withPivot(['quantity', 'price', 'discount', 'batch_id', 'name'])->withTimestamps();
    }

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }

    public function toppings()
    {
        return $this->belongsToMany(Topping::class)
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
                'amount', 'number', 'note', 'picture', 'payment_name', 'account_name'
            ])->withTimestamps();
    }
}
