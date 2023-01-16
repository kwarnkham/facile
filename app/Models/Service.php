<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public function orders()
    {
        return $this->belongsToMany(Order::class)
            ->withPivot(['price', 'quantity', 'discount', 'cost', 'name'])->withTimestamps();
    }

    public static function mapForOrder(array $data)
    {
        $services = static::whereIn('id', array_map(fn ($v) => $v['id'], $data))->get();

        $mappedServices = collect($data)->map(function ($service) use ($services) {
            $services->each(function ($val) use (&$service) {
                if ($val->id == $service['id']) {
                    $service['price'] = $val->price;
                    $service['cost'] = $val->cost;
                    $service['name'] = $val->name;
                }
            });
            return $service;
        })->toArray();
        return $mappedServices;
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
        );
    }
}
