<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    public function features()
    {
        return $this->morphedByMany(Feature::class, 'discountable');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}