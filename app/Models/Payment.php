<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class)->using(MerchantPayment::class)->withPivot('number', 'status', 'id')->withTimestamps();
    }
}
