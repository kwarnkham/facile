<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    public function features()
    {
        return $this->belongsToMany(Feature::class)->withPivot(['quantity', 'price', 'discount'])->withTimestamps();
    }

    public function merchantPayments()
    {
        return $this->belongsToMany(MerchantPayment::class, 'order_payment', 'order_id', 'merchant_payment_id')->withPivot('amount', 'number', 'note', 'picture')->withTimestamps();
    }

    public function getFeatureDiscounts()
    {
        return floor((float)$this->features->reduce(function ($carry, $feature) {
            return $feature->pivot->discount * $feature->pivot->quantity + $carry;
        }, 0));
    }

    public function paidAmount()
    {
        return (float)$this->merchantPayments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry, 0);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
