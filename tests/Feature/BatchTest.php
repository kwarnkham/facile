<?php

namespace Tests\Product;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Item;
use Tests\TestCase;

class BatchTest extends TestCase
{
    public function test_correct_stock()
    {
        $stock = rand(10, 100);
        $item = Item::factory()->create();
        $product = Product::factory()->make([
            'stock' => $stock,
            'item_id' => $item->id,
        ]);
        $product->purchase_price = floor($product->price * 0.5);
        $this->actingAs($this->user)->postJson(route('products.store', $product->toArray()));
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 1);

        $batch = Batch::first();
        $this->actingAs($this->user)->postJson(
            route('batches.correct', ['batch' => $batch->id]),
            [
                'stock' => 1,
                'type' => 1
            ]
        );

        $this->assertDatabaseCount('corrections', 1);

        $product = Product::first();
        $this->assertEquals($product->stock, $stock - 1);
        $this->assertEquals($batch->fresh()->stock, $stock - 1);

        $this->actingAs($this->user)->postJson(
            route('batches.correct', ['batch' => $batch->id]),
            [
                'stock' => 1,
                'type' => 2
            ]
        );

        $this->assertDatabaseCount('corrections', 2);
        $this->assertEquals($product->fresh()->stock, $stock);
        $this->assertEquals($batch->fresh()->stock, $stock);
    }
}
