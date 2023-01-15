<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Picture;
use App\Models\Purchase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemFeatureTest extends TestCase
{

    public function test_add_feature_to_an_item()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->user)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals($data['stock'], Purchase::first()->quantity);
        $this->assertDatabaseCount('batches', 1);
        $this->assertEquals($data['stock'], Batch::first()->stock);
    }

    public function test_add_feature_to_an_item_with_picture()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $image = UploadedFile::fake()->image('foo.jpg');
        $data['picture'] = $image;
        $this->actingAs($this->user)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price', 'picture')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->user)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals($data['stock'], Purchase::first()->quantity);
        $this->assertDatabaseCount('batches', 1);
        $this->assertEquals($data['stock'], Batch::first()->stock);
        $this->assertTrue(Picture::deletePictureFromDisk($image->hashName(), 'purchases'));
    }


    public function test_update_feature_of_an_item()
    {
        $item = Item::factory()->has(Feature::factory())->create();
        $data = Feature::factory()->make()->toArray();
        $this->actingAs($this->user)->put(
            route('features.update', ['feature' => $item->features()->first()->id]),
            $data
        );

        unset($data['stock']);
        $this->assertDatabaseHas('features', $data);
        $this->assertDatabaseCount('features', 1);
    }

    public function test_cannot_update_feature_stock()
    {
        $item = Item::factory()->has(Feature::factory())->create();
        $oldStock = $item->features()->first()->stock;
        $data = Feature::factory()->make()->toArray();
        $data['item_id'] = $item->id;
        $this->actingAs($this->user)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data);

        $this->assertDatabaseCount('features', 1);
        $this->assertEquals(Feature::first()->stock, $oldStock);
    }

    public function test_purchase_is_created_with_feature()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->user)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(Purchase::first()->purchasable_id, Feature::first()->id);
        $this->assertEquals(Purchase::first()->purchasable_type, Feature::class);
    }

    public function test_feature_qr_is_deleted_after_model_is_deleted()
    {
        $feature = Feature::factory()->for(Item::factory())->create();
        if (method_exists($feature, 'qr')) {
            $feature->qr();
            $file = $feature->qrFilePath();
            $this->assertTrue(Storage::exists($file));
            $feature->fresh()->delete();
            $this->assertFalse(Storage::exists($file));
        } else {
            $this->assertDatabaseCount('features', 1);
        }
    }

    public function test_restock_the_feature()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->features()->first()->name, $data['name']);
        $this->actingAs($this->user)->post(route('features.store'), $data)->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(Purchase::first()->price, $data['purchase_price']);

        $feature = Feature::first();
        $quantity = rand(1, 10);
        $price = rand(1000, 10000);
        $this->actingAs($this->user)->post(route('features.restock', ['feature' => $feature->id]), [
            'price' => $price,
            'quantity' => $quantity,
        ]);
        $this->assertEquals($feature->stock + $quantity, $feature->fresh()->stock);
        $this->assertDatabaseCount('purchases', 2);
        $purchase = Purchase::orderBy('id', 'desc')->first();
        $this->assertEquals($purchase->price, $price);
        $this->assertDatabaseCount('batches', 2);
        $this->assertEquals(Batch::orderBy('id', 'desc')->first()->stock, $purchase->quantity);
    }
}
