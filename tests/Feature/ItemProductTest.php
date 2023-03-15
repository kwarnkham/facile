<?php

namespace Tests\Product;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Item;
use App\Models\Picture;
use App\Models\Purchase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemProductTest extends TestCase
{

    public function test_add_product_to_an_item()
    {
        $data = Product::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->postJson(route('products.store'), $data);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->products()->first()->name, $data['name']);
        $this->actingAs($this->user)->postJson(route('products.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals($data['stock'], Purchase::first()->quantity);
        $this->assertDatabaseCount('batches', 1);
        $this->assertEquals($data['stock'], Batch::first()->stock);
    }

    public function test_add_product_to_an_item_with_picture()
    {
        $data = Product::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $image = UploadedFile::fake()->image('foo.jpg');
        $data['picture'] = $image;
        $this->actingAs($this->user)->postJson(route('products.store'), $data);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', collect($data)->except('purchase_price', 'picture')->toArray());
        $this->assertEquals($item->products()->first()->name, $data['name']);
        $this->actingAs($this->user)->postJson(route('products.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals($data['stock'], Purchase::first()->quantity);
        $this->assertDatabaseCount('batches', 1);
        $this->assertEquals($data['stock'], Batch::first()->stock);
        $this->assertTrue(Picture::deletePictureFromDisk($image->hashName(), 'purchases'));
    }


    public function test_update_product_of_an_item()
    {
        $item = Item::factory()->has(Product::factory())->create();
        $data = Product::factory()->make()->toArray();
        $this->actingAs($this->user)->put(
            route('products.update', ['product' => $item->products()->first()->id]),
            $data
        );

        unset($data['stock']);
        $this->assertDatabaseHas('products', $data);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_cannot_update_product_stock()
    {
        $item = Item::factory()->has(Product::factory())->create();
        $oldStock = $item->products()->first()->stock;
        $data = Product::factory()->make()->toArray();
        $data['item_id'] = $item->id;
        $this->actingAs($this->user)->put(route('products.update', ['product' => $item->products()->first()->id]), $data);

        $this->assertDatabaseCount('products', 1);
        $this->assertEquals(Product::first()->stock, $oldStock);
    }


    public function test_purchase_is_created_with_product()
    {
        $data = Product::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->postJson(route('products.store'), $data);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->products()->first()->name, $data['name']);
        $this->actingAs($this->user)->postJson(route('products.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(Purchase::first()->purchasable_id, Product::first()->id);
        $this->assertEquals(Purchase::first()->purchasable_type, Product::class);
    }

    public function test_product_qr_is_deleted_after_model_is_deleted()
    {
        $product = Product::factory()->for(Item::factory())->create();
        if (method_exists($product, 'qr')) {
            $product->qr();
            $file = $product->qrFilePath();
            $this->assertTrue(Storage::exists($file));
            $product->fresh()->delete();
            $this->assertFalse(Storage::exists($file));
        } else {
            $this->assertDatabaseCount('products', 1);
        }
    }

    public function test_restock_the_product()
    {
        $data = Product::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $data['purchase_price'] = floor($data['price'] * 0.9);
        $this->actingAs($this->user)->postJson(route('products.store'), $data);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', collect($data)->except('purchase_price')->toArray());
        $this->assertEquals($item->products()->first()->name, $data['name']);
        $this->actingAs($this->user)->postJson(route('products.store'), $data)->assertUnprocessable();
        $this->assertDatabaseCount('purchases', 1);
        $this->assertEquals(Purchase::first()->price, $data['purchase_price']);

        $product = Product::first();
        $quantity = rand(1, 10);
        $price = rand(1000, 10000);

        $image = UploadedFile::fake()->image('foo.jpg');
        $this->actingAs($this->user)->postJson(route('products.restock', ['product' => $product->id]), [
            'price' => $price,
            'quantity' => $quantity,
            'picture' => $image
        ]);
        $this->assertEquals($product->stock + $quantity, $product->fresh()->stock);
        $this->assertDatabaseCount('purchases', 2);
        $purchase = Purchase::orderBy('id', 'desc')->first();
        $this->assertEquals($purchase->price, $price);
        $this->assertDatabaseCount('batches', 2);
        $this->assertEquals(Batch::orderBy('id', 'desc')->first()->stock, $purchase->quantity);
        $this->assertTrue(Picture::deletePictureFromDisk($image->hashName(), 'purchases'));
    }
}
