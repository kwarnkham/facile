<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function features()
    {
        return $this->belongsToMany(Feature::class)->withPivot(['quantity', 'price'])->withTimestamps();
    }

    public function payments()
    {
        return $this->belongsToMany(UserPayment::class, 'order_payment', 'order_id', 'user_payment_id')->withPivot('amount', 'number')->withTimestamps();
    }
}
