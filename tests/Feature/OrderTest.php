<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;


    public function test_create_an_order()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        dump(Order::find(1))->toArray();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('feature_order', $count);
    }
}
