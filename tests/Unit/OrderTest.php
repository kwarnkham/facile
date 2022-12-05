<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_get_feature_discounts()
    {

        $data = ['merchant_id' => $this->merchant->merchant->id];
        $features = Feature::factory()->for(Item::factory()->state($data))->create();

        $order = Order::factory()->create([
            'amount' => Feature::all()->reduce(fn ($carry, $v) => $carry + $v->price, 0), 'merchant_id' => $this->merchant->merchant->id,
        ]);

        $features->each(fn ($f) => $order->features()->attach($f->id, ['price' => $f->price, 'quantity' => rand(1, 10), 'discount' => floor($f->price * 0.1)]));

        $this->assertEquals(
            $order->getFeatureDiscounts(),
            $order->features->reduce(fn ($carry, $val) => $carry + ($val->pivot->discount * $val->pivot->quantity), 0)
        );
    }
}
