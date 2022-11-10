<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Merchant;
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
        $features = Feature::factory(10)->for(Item::factory()->state(['user_id' => $this->merchant->id]))->create();
        $order = Order::factory()->create([
            'user_id' => $this->merchant->id,
            'amount' => $features->reduce(fn ($carry, $feature) => $feature->price + $carry, 0)
        ]);
        $features->each(fn ($feature) => $order->features()->attach($feature->id, ['price' => $feature->price, 'quantity' => $feature->id]));

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($order->amount / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($order->amount / 4)
        ]);
        $this->assertDatabaseCount('order_payment', 2);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount - $order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);
        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status,  3);

        $user = User::factory()->hasAttached(Role::where('name', 'merchant')->first())->has(Merchant::factory())->create();
        $user->merchant->payments()->attach(Payment::factory()->create());
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $user->merchant->payments()->first()->pivot->id,
            'amount' => '1000'
        ])->assertSessionHasErrors(['payment_id']);
    }

    public function test_pay_order_fully()
    {
        $features = Feature::factory(10)->for(Item::factory()->state(['user_id' => $this->merchant->id]))->create();
        $order = Order::factory()->create([
            'user_id' => $this->merchant->id,
            'amount' => $features->reduce(fn ($carry, $feature) => $feature->price + $carry, 0)
        ]);
        $features->each(fn ($feature) => $order->features()->attach($feature->id, ['price' => $feature->price, 'quantity' => $feature->id]));

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_cannot_pay_more_than_order_amount()
    {
        $features = Feature::factory(10)->for(Item::factory()->state(['user_id' => $this->merchant->id]))->create();
        $order = Order::factory()->create([
            'user_id' => $this->merchant->id,
            'amount' => $features->reduce(fn ($carry, $feature) => $feature->price + $carry, 0)
        ]);
        $features->each(fn ($feature) => $order->features()->attach($feature->id, ['price' => $feature->price, 'quantity' => $feature->id]));

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($order->amount / 2)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($order->amount / 4)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount - $order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);

        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status, 3);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount
        ])->assertSessionHasErrors(['amount']);
    }
}
