<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable')->orderBy('id', 'desc');
    }
}
