<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Feature;
use App\Models\Item;
use Tests\TestCase;

class BatchTest extends TestCase
{
    public function test_correct_stock()
    {
        $stock = rand(10, 100);
        $item = Item::factory()->create();
        $feature = Feature::factory()->make([
            'stock' => $stock,
            'item_id' => $item->id,
        ]);
        $feature->purchase_price = floor($feature->price * 0.5);
        $this->actingAs($this->user)->post(route('features.store', $feature->toArray()));
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseCount('batches', 1);

        $batch = Batch::first();
        $this->actingAs($this->user)->post(
            route('batches.correct', ['batch' => $batch->id]),
            [
                'stock' => 1,
                'type' => 1
            ]
        );

        $this->assertDatabaseCount('corrections', 1);

        $feature = Feature::first();
        $this->assertEquals($feature->stock, $stock - 1);
        $this->assertEquals($batch->fresh()->stock, $stock - 1);

        $this->actingAs($this->user)->post(
            route('batches.correct', ['batch' => $batch->id]),
            [
                'stock' => 1,
                'type' => 2
            ]
        );

        $this->assertDatabaseCount('corrections', 2);
        $this->assertEquals($feature->fresh()->stock, $stock);
        $this->assertEquals($batch->fresh()->stock, $stock);
    }
}
