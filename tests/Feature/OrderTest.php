<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
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

    public function makeOrder($featureDiscountPercentage = 0, $orderDiscountRatio = 0, $depositRatio = 0)
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        for ($i = 0; $i < rand(1, 10); $i++) {
            $feature = Feature::factory()->make(['stock' => $stock]);
            $this->actingAs($this->merchant)->post(route('features.store'), [
                ...['item_id' => $item->id],
                ...$feature->toArray(),
                ...['purchase_price' => floor($feature->price * 0.9)]
            ]);
        }
        if ($featureDiscountPercentage > 0) {
            $features = Feature::where('item_id', $item->id)->with(['discounts'])->get();
            $features->each(fn ($v) => $this->actingAs($this->merchant)->post(route('features.discount', [
                'feature' => $v->id
            ]), [
                'discount_id' => Discount::factory()->create(['merchant_id' => $this->merchant->merchant->id, 'percentage' => $featureDiscountPercentage])->id
            ]));
        }


        $features = Feature::where('item_id', $item->id)->with(['discounts'])->get();
        $feats = $features->map(fn ($feature) => [
            'id' => $feature->id,
            'quantity' => $stock
        ])->toArray();


        $remaining = floor((float)($features->reduce(fn ($carry, $feat) => ($feat->price - $feat->totalDiscount()) * collect($feats)->first(fn ($v) => $v['id'] == $feat->id)['quantity'] + $carry)));


        $data = [
            ...['features' => $feats]
        ];
        if ($depositRatio > 0) {
            $data = [
                ...$data,
                ...['deposit' => floor($remaining / $depositRatio)]
            ];
        }
        if ($orderDiscountRatio > 0) {
            $data = [
                ...$data,
                'discount' => floor($remaining / $orderDiscountRatio)
            ];
        }


        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...$data,
            ...Order::factory()->make()->toArray()
        ]);

        return $remaining;
    }
    public function test_create_an_order()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $count = rand(1, 4);
        $features = Feature::factory($count)->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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

    public function test_cancel_an_order()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $count = rand(1, 4);
        $features = Feature::factory($count)->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();
        $this->actingAs($this->merchant)->post(route('orders.cancel', ['order' => $order->id]));
        $this->assertEquals(OrderStatus::CANCELED->value, $order->fresh()->status);
    }

    public function test_stock_is_restocked_when_order_is_canceled()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $count = rand(1, 4);
        $features = Feature::factory($count)->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();
        $this->actingAs($this->merchant)->post(route('orders.cancel', ['order' => $order->id]));
        $this->assertEquals(OrderStatus::CANCELED->value, $order->fresh()->status);

        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, $stock));
    }

    public function test_create_an_order_with_order_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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

    public function test_out_of_stock_feature_cannot_be_created_for_order()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 1);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
            ],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, 0));
    }

    public function test_stock_is_reduced_properly()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = 10;
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => 5]
        )->toArray();

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 1);
        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, $stock - 5));

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 2);
        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, 0));
    }

    public function test_create_an_order_with_full_order_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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
        $stock = rand(2, 14);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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
        $stock = rand(2, 14);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $features_b = $features;
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => [...$features, ...$features_b]],
            ...Order::factory()->make()->toArray()
        ])->assertSessionHasErrors(['features.0.id']);
    }

    public function test_pay_order_using_payment()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($remaining / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => floor($remaining / 4)
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
            'amount' => $remaining - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);
        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status,  3);
    }

    public function test_pay_order_fully()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $features = Feature::factory(rand(2, 14))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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
        $stock = rand(1, 10);
        $features = Feature::factory($count)->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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
        $stock = rand(1, 10);
        $features = Feature::factory(rand(2, 14))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $discount = floor((float)(Feature::all()->reduce(function ($carry, $feature) use ($features) {
            $amount = collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'] * $feature->price;
            return $carry + $amount;
        })) / 2);
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $discount],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);
        $order = Order::first();
        $this->assertEquals(floor($order->amount / 2), $order->discount);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $order->amount - $order->discount
        ]);

        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_that_has_discount_features()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 10);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => floor($stock / 2)]
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
                'amount' => floor(($order->amount - $order->getFeatureDiscounts()) / 2)
            ]);

            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => $order->amount - $order->getFeatureDiscounts() - floor(($order->amount - $order->getFeatureDiscounts()) / 2)
            ]);

            $this->assertDatabaseCount('order_payment', 3);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }

    public function test_pay_discount_order_that_has_discount_features_and_deposit()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $percentage = rand(1, 100) / 100;
        $discount = Discount::factory()->create(['percentage' => $percentage * 100, 'merchant_id' => $this->merchant->merchant->id]);


        Feature::where('item_id', $item->id)->get()->each(fn ($feature) => $this->actingAs($this->merchant)->post(route('features.discount', ['feature' => $feature->id]), [
            'discount_id' => $discount->id
        ])->assertSessionMissing('errror'));

        $orderDiscount = floor((float)(Feature::with(['discounts'])->get()->reduce(fn ($carry, $v) => ($v->price - $v->totalDiscount()) * collect($features)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry, 0)) / 10);

        $deposit = floor($orderDiscount / 1);

        if ($orderDiscount <= 0) return;


        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $orderDiscount, 'deposit' => $deposit],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        if ($order->status != 3) {
            $remaining = $order->amount - $order->discount - $order->deposit - $order->getFeatureDiscounts();
            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => floor($remaining / 2)
            ]);

            $this->assertDatabaseCount('order_payment', 1);
            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => floor($remaining / 4)
            ]);

            $this->assertDatabaseCount('order_payment', 2);
            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
                'amount' => $remaining - floor($remaining / 2) - floor($remaining / 4)
            ]);

            $this->assertDatabaseCount('order_payment', 3);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }


    public function test_all_discount_cannot_be_greater_than_order_amount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
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
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $orderDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(Order::first()->status, 3);
    }

    public function test_create_order_with_deposit()
    {
        $remaining = $this->makeOrder(depositRatio: 4);
        $this->assertEquals(Order::first()->deposit, floor($remaining / 4));
    }

    public function test_order_deposit_is_valid()
    {
        $this->makeOrder(depositRatio: 0.9);
        $this->assertDatabaseCount('orders', 0);

        $this->makeOrder(depositRatio: 11 - 1, orderDiscountRatio: 1.1);
        $this->assertDatabaseCount('orders', 0);

        $remaining = $this->makeOrder(depositRatio: 11, orderDiscountRatio: 1.1);
        $this->assertDatabaseCount('orders', 1);

        $this->assertEquals(Order::first()->amount, $remaining);
    }

    public function test_pay_order_with_note()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();
        $note = 'payment note';
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->pivot->id,
            'amount' => $remaining,
            'note' => $note
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  3);
        $this->assertEquals($order->fresh()->payments->first()->pivot->note,  $note);
    }

    // public function test_complete_the_order()
    // {
    //     $this->makeOrder();
    //     $order = Order::first();
    //     $this->actingAs($this->merchant)->post(route('orders.complete', ['order' => $order->id]));
    //     $this->assertEquals($order->fresh()->status, 3);
    // }
}
