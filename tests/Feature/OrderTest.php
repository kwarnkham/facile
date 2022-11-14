<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Discount;
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
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
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
        $amount = (float)Feature::where('item_id', $item->id)->get()->reduce(fn ($carry, $feature) => $carry + $feature->price * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'], 0);
        $this->assertEquals(Order::first()->amount, $amount);
    }

    public function test_create_an_order_with_order_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $amount = (float)Feature::where('item_id', $item->id)->get()->reduce(fn ($carry, $feature) => $carry + $feature->price * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'], 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
                'discount' => $amount / 10
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertEquals(Order::first()->amount / 10, $amount / 10);
    }

    public function test_create_an_order_with_full_order_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $amount = (float)Feature::where('item_id', $item->id)->get()->reduce(fn ($carry, $feature) => $carry + $feature->price * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'], 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
                'discount' => $amount
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $order = Order::first();
        $this->assertEquals($order->status, 3);
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => 0
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value)->assertSessionHas('message', 'Order cannot be paid anymore');
    }

    public function test_create_an_order_with_order_discount_and_feature_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $discount = Discount::factory()->create(['merchant_id' => $this->merchant->id, 'percentage' => 10]);
        Feature::where('item_id', $item->id)->each(fn ($feat) => $feat->discounts()->attach($discount->id));
        $amount = (float)Feature::where('item_id', $item->id)->with(['discounts'])->get()->reduce(fn ($carry, $feature) => $carry + (($feature->price - $feature->totalDiscount()) * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity']), 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
                'discount' => $amount / 10
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);
        $order = Order::first();
        $this->assertEquals(round($order->amount - $order->getFeatureDiscounts(), 2), round($amount, 2));
        $this->assertEquals(
            round($order->getFeatureDiscounts(), 2),
            round((float)($order->features()->with(['discounts'])->get()->reduce(fn ($carry, $val) => $carry + $val->totalDiscount() * $val->pivot->quantity, 0)), 2)
        );
    }

    public function test_order_feature_id_is_distinct()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
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
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();

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

        $user = User::factory()->hasAttached(Role::where('name', 'merchant')->first())->has(Merchant::factory())->create();
        $user->merchant->payments()->attach(Payment::factory()->create());
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $user->merchant->payments()->first()->pivot->id,
            'amount' => '1000'
        ])->assertSessionHasErrors(['payment_id']);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);
        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status,  3);
    }

    public function test_pay_order_fully()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);

        $order = Order::first();

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_cannot_pay_more_than_order_amount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);

        $order = Order::first();

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
            'amount' => $order->amount * 2
        ])->assertSessionHasErrors(['amount']);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);

        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_with_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(2, 14);
        $features = Feature::factory($count)->create(['item_id' => $item->id])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $discount = (float)(Feature::all()->reduce(function ($carry, $feature) use ($features) {
            $amount = collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'] * $feature->price;
            return $carry + $amount;
        })) / 2;
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $discount],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);
        $order = Order::first();
        $this->assertEquals($order->amount / 2, $order->discount);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount / 2
        ]);

        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_that_has_discount_features()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);

        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => rand(1, 10)]
        )->toArray();
        $percentage = rand(1, 100) / 100;
        $discount = Discount::factory()->create(['percentage' => $percentage * 100, 'merchant_id' => $this->merchant->merchant->id]);

        Feature::all()->each(fn ($feature) => $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ])->assertSessionMissing('errror'));

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);
        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();
        if ($order->status != 3) {
            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => $order->amount
            ])->assertSessionHasErrors(['amount']);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => $order->amount - $order->getFeatureDiscounts()
            ]);

            $this->assertDatabaseCount('order_payment', 1);
            $this->assertEquals($order->fresh()->status, 3);
        }

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);
        $this->assertDatabaseCount('orders', 2);

        $order = Order::orderBy('id', 'desc')->first();
        if ($order->status != 3) {
            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => ($order->amount - $order->getFeatureDiscounts()) / 2
            ]);

            $this->assertDatabaseCount('order_payment', 2);
            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => ($order->amount - $order->getFeatureDiscounts()) / 2
            ]);

            $this->assertDatabaseCount('order_payment', 3);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }

    public function test_pay_discount_order_that_has_discount_features()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $count = rand(1, 10);

        $features = Feature::factory($count)->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => 1]
        )->toArray();
        $percentage = rand(1, 100) / 100;
        $discount = Discount::factory()->create(['percentage' => $percentage * 100, 'merchant_id' => $this->merchant->merchant->id]);


        Feature::where('item_id', $item->id)->get()->each(fn ($feature) => $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ])->assertSessionMissing('errror'));

        $orderDiscount = (float)(Feature::with(['discounts'])->get()->reduce(fn ($carry, $v) => ($v->price - $v->totalDiscount()) * collect($features)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry, 0)) / 10;

        if ($orderDiscount <= 0) return;


        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $orderDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        if ($order->status != 3) {
            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => round(($order->amount - $orderDiscount - $order->getFeatureDiscounts()) / 2, 2)
            ]);

            $this->assertDatabaseCount('order_payment', 1);
            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => round(($order->amount - $orderDiscount - $order->getFeatureDiscounts()) / 4, 2)
            ]);

            $this->assertDatabaseCount('order_payment', 2);
            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => round($order->amount - $order->paidAmount() - $orderDiscount - $order->getFeatureDiscounts(), 2)
            ]);

            $this->assertDatabaseCount('order_payment', 3);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }


    public function test_all_discount_cannot_be_greater_than_order_amount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => 1]
        )->toArray();
        $percentage = 50;
        $discount = Discount::factory()->create(['percentage' => $percentage * 100, 'merchant_id' => $this->merchant->merchant->id]);

        Feature::where('item_id', $item->id)->get()->each(fn ($feature) => $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ])->assertSessionMissing('errror'));

        $orderDiscount = (float)(Feature::with(['discounts'])->get()->reduce(fn ($carry, $v) => ($v->price - $v->totalDiscount()) * collect($features)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry, 0));

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $orderDiscount + 1],
            ...Order::factory()->make()->toArray()
        ])->assertSessionHas('message', 'Total discount is greater than the amount');

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $orderDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(Order::first()->status, 3);
    }
}
