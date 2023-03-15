<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
