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

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable');
    }
}
