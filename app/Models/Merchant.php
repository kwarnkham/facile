<?php

namespace App\Models;

use App\Traits\Spaceable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory, Spaceable;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable');
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'merchant_payments')->using(MerchantPayment::class)->withPivot('number', 'status', 'id')->withTimestamps();
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
