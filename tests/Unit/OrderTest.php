<?php

namespace Tests\Unit;

use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use Tests\TestCase;

class OrderTest extends TestCase
{
    protected $tenancy = true;

    public function test_get_feature_discounts()
    {
        $features = Feature::factory()->for(Item::factory())->create();
        $feature = Feature::first();
        $purchase = $feature->purchases()->create([
            'price' => $feature->price * 0.9,
            'quantity' => $feature->stock
        ]);
        $batch = $feature->batches()->create([
            'purchase_id' => $purchase->id,
            'stock' => $feature->stock,
        ]);
        $order = Order::factory()->create([
            'amount' => Feature::all()->reduce(fn ($carry, $v) => $carry + $v->price, 0),
        ]);

        $features->each(fn ($f) => $order->features()->attach($f->id, [
            'price' => $f->price,
            'quantity' => rand(1, 10),
            'discount' => floor($f->price * 0.1),
            'batch_id' => $batch->id
        ]));

        $this->assertEquals(
            $order->getFeatureDiscounts(),
            $order->features->reduce(fn ($carry, $val) => $carry + ($val->pivot->discount * $val->pivot->quantity), 0)
        );
    }
}
