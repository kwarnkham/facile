<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Discount;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Purchase;
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
    }

    public function test_update_feature_of_an_item()
    {
        $item = Item::factory()->has(Feature::factory())->create(['merchant_id' => $this->merchant->merchant->id]);
        $data = Feature::factory()->make()->toArray();
        $data['item_id'] = $item->id;
        $this->actingAs($this->merchant)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data);
        $this->assertDatabaseHas('features', $data);
        $this->assertDatabaseCount('features', 1);
        $data['item_id'] = $item->id + rand(1, 10);
        $this->actingAs($this->merchant)->put(route('features.update', ['feature' => $item->features()->first()->id]), $data)->assertStatus(ResponseStatus::UNAUTHORIZED->value);
    }

    public function test_apply_a_discount()
    {
        $discount = Discount::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $feature = Feature::factory()->for(Item::factory()->state(['merchant_id' => $this->merchant->merchant->id]))->create();
        $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ]);

        $this->assertDatabaseCount('discountables', 1);
        $this->assertEquals($feature->fresh()->discounts->count(), 1);
    }

    public function test_total_discount_limit()
    {
        $discount = Discount::factory()->create(['merchant_id' => $this->merchant->merchant->id, 'percentage' => 60]);
        $feature = Feature::factory()->for(Item::factory()->state(['merchant_id' => $this->merchant->merchant->id]))->create();
        $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ]);

        $discount = Discount::factory()->create(['merchant_id' => $this->merchant->merchant->id, 'percentage' => 60]);
        $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ])->assertSessionHas('error');
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
}
