<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    use HasFactory;

    public function orders()
    {
        return $this->belongsToMany(Order::class)->withPivot(['price', 'quantity', 'discount', 'cost']);
    }

    public static function mapForOrder(array $data)
    {
        $toppings = static::whereIn('id', array_map(fn ($v) => $v['id'], $data))->get();

        $mappedToppings = collect($data)->map(function ($topping) use ($toppings) {
            $toppings->each(function ($val) use (&$topping) {
                if ($val->id == $topping['id']) {
                    $topping['price'] = $val->price;
                    $topping['cost'] = $val->cost;
                }
            });
            return $topping;
        })->toArray();
        return $mappedToppings;
    }
}
