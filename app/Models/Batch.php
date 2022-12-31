<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }
}
