<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_get_product_discounts()
    {
        $products = Product::factory()->for(Item::factory())->create();
        $product = Product::first();
        $purchase = $product->purchases()->create([
            'price' => $product->price * 0.9,
            'quantity' => $product->stock,
            'name' => $product->name
        ]);
        $product->batches()->create([
            'purchase_id' => $purchase->id,
            'stock' => $product->stock,
        ]);
        $order = Order::factory()->create([
            'user_id' => User::first()->id,
            'updated_by' => User::first()->id,
            'amount' => Product::all()->reduce(fn ($carry, $v) => $carry + $v->price, 0),
        ]);

        $products->each(fn ($f) => $order->products()->attach($f->id, [
            'price' => $f->price,
            'quantity' => rand(1, 10),
            'discount' => floor($f->price * 0.1),
            'name' => $f->name,
            'purchase_price' => $f->price * 0.8
        ]));

        $this->assertEquals(
            $order->getProductDiscounts(),
            $order->products->reduce(fn ($carry, $val) => $carry + ($val->pivot->discount * $val->pivot->quantity), 0)
        );
    }

    public function test_create_unstocked_order()
    {
        $items = Item::factory(rand(1, 5))->create()->map(fn ($item) => [
            'id' => $item->id,
            'price' => rand(1000, 5000),
            'quantity' => rand(1, 10)
        ]);
        $amount = $items->reduce(fn ($carry, $item) => $carry + $item['price'] * $item['quantity'], 0);
        $data = [
            'discount' => floor((int)$amount / 2),
            'customer' => 'account_name',
            'phone' => 'phone',
            'address' => 'address',
            'note' => 'note',
            'items' => $items->toArray()
        ];
        $this->actingAs($this->user)->postJson(route('orders.preOrder'), $data);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', collect([...$data, 'amount' => $amount])->except(['items'])->toArray());
        $this->assertDatabaseCount('item_order', $items->count());
        Order::first()->items->each(fn ($item) => $this->assertDatabaseHas('item_order', $item->pivot->toArray()));
    }
}
