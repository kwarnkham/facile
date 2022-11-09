<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class OrderTest extends TestCase
{
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

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('feature_order', $count);
    }

    public function test_order_feature_id_is_distinct()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $features_b = $features;
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => [...$features, ...$features_b]],
            ...Order::factory()->make()->toArray()
        ])->assertSessionHasErrors(['features.0.id']);
    }

    public function test_pay_order_using_payment()
    {
        $order = Order::factory()->create(['user_id' => $this->merchant->id, 'amount' => 123]);
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->payments()->first()->pivot->id,
            'amount' => '1000'
        ]);
        $this->assertDatabaseCount('order_payment', 1);

        $user = User::factory()->hasAttached(Role::where('name', 'merchant')->first())->create();
        $user->payments()->attach(Payment::factory()->create());
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $user->payments()->first()->pivot->id,
            'amount' => '1000'
        ])->assertSessionHasErrors(['payment_id']);
    }
}
