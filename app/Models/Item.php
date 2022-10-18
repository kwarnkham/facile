<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable');
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['user_id'] ?? null,
            fn (Builder $query, $user_id) => $query->where('user_id', $user_id)
        )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('price', 'like', '%' . $search . '%');
                    // ->orWhereHas('organization', function ($query) use ($search) {
                    //     $query->where('name', 'like', '%' . $search . '%');
                    // });
                })
            );
    }
}
