<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemFeatureTest extends TestCase
{
    public function test_add_feature_to_an_item()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->merchant)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->merchant)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_update_feature_of_an_item()
    {
        $item = Item::factory()->has(Feature::factory())->create(['merchant_id' => $this->merchant->merchant->id]);
        $data = Feature::factory()->make()->toArray();
        unset($data['stock']);
        $data['item_id'] = $item->id;
        $this->actingAs($this->merchant)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data);
        $this->assertDatabaseHas('features', $data);
        $this->assertDatabaseCount('features', 1);
        $data['item_id'] = $item->id + rand(1, 10);
        $this->actingAs($this->merchant)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data)->assertStatus(ResponseStatus::UNAUTHORIZED->value);
    }

    public function test_cannot_update_feature_stock()
    {
        $item = Item::factory()->has(Feature::factory())->create(['merchant_id' => $this->merchant->merchant->id]);
        $oldStock = $item->features()->first()->stock;
        $data = Feature::factory()->make()->toArray();
        $data['item_id'] = $item->id;
        $this->actingAs($this->merchant)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data);

        $this->assertDatabaseCount('features', 1);
        $this->assertEquals(Feature::first()->stock, $oldStock);
    }

    public function test_purchase_is_created_with_feature()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->merchant)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->merchant)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(Purchase::first()->purchasable_id, Feature::first()->id);
        $this->assertEquals(Purchase::first()->purchasable_type, Feature::class);
    }

    public function test_feature_qr_is_deleted_after_model_is_deleted()
    {
        $feature = Feature::factory()->for(Item::factory()->state(['merchant_id' => $this->merchant->merchant->id]))->create();
        $feature->qr();
        $file = $feature->qrFilePath();
        $this->assertTrue(Storage::exists($file));
        $feature->fresh()->delete();
        $this->assertFalse(Storage::exists($file));
    }

    public function test_restock_the_feature()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->merchant)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->merchant)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);

        $feature = Feature::first();
        $quantity = rand(1, 10);
        $this->actingAs($this->merchant)->post(route('features.restock', ['feature' => $feature->id]), [
            'price' => rand(1000, 10000),
            'quantity' => $quantity
        ]);
        $this->assertEquals($feature->stock + $quantity, $feature->fresh()->stock);
        $this->assertDatabaseCount('purchases', 2);
    }
}
