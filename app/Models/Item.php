<?php

namespace App\Models;

use App\Traits\Spaceable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory, Spaceable;

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'pictureable')->orderBy('id', 'desc');
    }

    public function wholesales()
    {
        return $this->hasMany(Wholesale::class)->orderBy('quantity');
    }

    public function features()
    {
        return $this->hasMany(Feature::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query
            ->when(
                $filters['selected_tags'] ?? null,
                fn (Builder $query, $selected_tags) => $query->whereHas('tags', function ($q) use ($selected_tags) {
                    $q->whereIn('tags.id', explode(',', $selected_tags));
                })
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, $status) => $query->where('status', $status)
            )
            ->when(
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
