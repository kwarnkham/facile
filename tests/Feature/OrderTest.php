<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ResponseStatus;
use App\Models\Credit;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picture;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{

    public function makeOrder($featureDiscountPercentage = 0, $orderDiscountRatio = 0)
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


        $features = Feature::where('item_id', $item->id)->get();
        $feats = $features->map(fn ($feature) => [
            'id' => $feature->id,
            'quantity' => $stock,
            'discount' => ($features->first(fn ($value) => $value->id == $feature->id)->price * $featureDiscountPercentage) / 100
        ])->map(function ($feature) {
            if ($feature['discount'] <= 0) return [
                'id' => $feature['id'],
                'quantity' => $feature['quantity']
            ];
            else return $feature;
        })->toArray();


        $remaining = floor(
            (float)$features->reduce(
                fn ($carry, $feat) => $feat->price * collect($feats)->first(fn ($v) => $v['id'] == $feat->id)['quantity'] + $carry,
                0
            )
        );


        $data = [
            ...['features' => $feats]
        ];

        if ($orderDiscountRatio > 0) {
            $data = [
                ...$data,
                'discount' => floor($remaining / $orderDiscountRatio)
            ];
        }
        $existed = Order::count();
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...$data,
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', $existed + 1);
        return $remaining;
    }

    public function test_make_an_order_for_unstocked_item()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $feature = Feature::factory()->make(['stock' => 50, 'type' => 2]);
        $this->actingAs($this->merchant)->post(route('features.store'), [
            ...['item_id' => $item->id],
            ...$feature->toArray(),
            ...['purchase_price' => floor($feature->price * 0.9)]
        ]);

        $features = Feature::where('item_id', $item->id)->get();

        $feats = $features->map(fn ($feature) => [
            'id' => $feature->id,
            'quantity' => 50
        ])->toArray();


        $remaining = floor(
            (float)$features->reduce(
                fn ($carry, $feat) => $feat->price * collect($feats)->first(fn ($v) => $v['id'] == $feat->id)['quantity'] + $carry,
                0
            )
        );

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $feats],
            ...Order::factory()->make()->toArray()
        ]);
        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();
        $order->features->each(fn ($v) => $this->assertEquals($v->type, 2));
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($remaining / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 2);
        $this->actingAs($this->merchant)->post(route('orders.cancel', ['order' => $order->id]));
        $this->assertEquals(Feature::first()->stock, 0);
        $this->assertDatabaseCount('credits', 1);
        $this->assertEquals(Credit::first()->amount, floor($remaining / 2));
    }

    public function test_cannot_cancel_a_completed_order_after_24_hours()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->id,
            'amount' => $remaining
        ]);

        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);
        $time = (clone $order->updated_at)->addHours(24);
        $this->travelTo($time);
        $this->actingAs($this->merchant)->post(route('orders.cancel', ['order' => $order->id]))->assertSessionHas('message', 'Cannot cancel a paid order after 24 hours');
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);
    }

    public function test_complete_an_order()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();

        $this->actingAs($this->merchant)->post(route('orders.complete', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::PENDING->value);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->id,
            'amount' => floor($remaining / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PARTIALLY_PAID->value);

        $this->actingAs($this->merchant)->post(route('orders.complete', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::PARTIALLY_PAID->value);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->merchant->merchant->payments()->first()->id,
            'amount' => $remaining - floor($remaining / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);

        $this->actingAs($this->merchant)->post(route('orders.complete', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::COMPLETED->value);
    }

    public function test_pay_order_with_picture()
    {
        $amount = $this->makeOrder();
        $order = Order::first();
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($amount / 5),
            'picture' => UploadedFile::fake()->image('screenshot.jpg')
        ]);

        $picture = $order->merchantPayments->first()->pivot->picture;
        $this->assertTrue(Storage::exists(Picture::picturePath($picture, 'payments')));
        $this->assertTrue(Picture::deletePictureFromDisk($picture, 'payments'));

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($amount / 5),
            'picture' => 'picture.jpg'
        ])->assertSessionHasErrors(['picture']);
    }

    public function test_create_an_order()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $count = rand(1, 4);
        $features = Feature::factory($count)->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => floor($stock / 2)]
        )->toArray();
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('feature_order', $count);
        $amount = (float)Feature::where('item_id', $item->id)->get()->reduce(fn ($carry, $feature) => $carry + $feature->price * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity'], 0);
        $this->assertEquals(Order::first()->amount, $amount);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
        ]);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_cancel_an_order()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), ['amount' => $remaining, 'payment_id' => $this->payment->id]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);

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
            'payment_id' => $this->payment->id,
            'amount' => 0
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value)->assertSessionHas('message', 'Order cannot be paid anymore');
    }

    public function test_create_an_order_with_order_discount_and_feature_discount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 14);
        $price = rand(10, 1000);
        $featureDiscount = 0.1;
        $features = Feature::factory(rand(1, 10))->create([
            'item_id' => $item->id,
            'stock' => $stock,
            'price' => $price
        ])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock, 'discount' => $price * $featureDiscount]
        )->toArray();

        $amount = (float)Feature::where('item_id', $item->id)->get()->reduce(
            fn ($carry, $feature) =>
            $carry + (($feature->price - ($feature->price * $featureDiscount)) * collect($features)->first(fn ($v) => $v['id'] == $feature->id)['quantity']),
            0
        );

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...[
                'features' => $features,
                'discount' => $amount * 0.1
            ],
            ...Order::factory()->make()->toArray()
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value);
        $order = Order::first();

        $this->assertEquals(floor($amount), $order->amount);
        $this->assertEquals($amount * 0.1, $order->discount);
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
            'payment_id' => $this->payment->id,
            'amount' => floor($remaining / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
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
            'payment_id' => $this->payment->id,
            'amount' => $remaining - (float)$order->merchantPayments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
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
            'payment_id' => $this->payment->id,
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
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 2)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 4)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount * 2
        ])->assertSessionHasErrors(['amount']);

        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - (float)$order->merchantPayments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
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
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - $order->discount
        ]);

        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_that_has_discount_features()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(2, 10);
        $percentage = rand(1, 100) / 100;
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($feature) =>
            [
                'id' => $feature->id,
                'quantity' => floor($stock / 2),
                'discount' => $feature->price * ($percentage / 100)
            ]
        )->toArray();
        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features],
            ...Order::factory()->make()->toArray()
        ]);
        $this->assertDatabaseCount('orders', 1);
        $order = Order::first();
        if ($order->status != 3) {
            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->payment->id,
                'amount' => $order->amount
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
                'payment_id' => $this->payment->id,
                'amount' => floor($order->amount  / 2)
            ]);

            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->payment->id,
                'amount' => $order->amount - floor($order->amount / 2)
            ]);

            $this->assertDatabaseCount('order_payment', 3);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }

    public function test_all_discount_cannot_be_greater_than_order_amount()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $stock = rand(1, 10);
        $discount = 0.5;
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($feature) =>
            [
                'id' => $feature->id,
                'quantity' => $stock,
                'discount' => $feature->price * $discount
            ]
        )->toArray();

        $halfDiscount = (float)(Feature::all()->reduce(
            fn ($carry, $v) => $v->price * $discount * collect($features)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry,
            0
        ));

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $halfDiscount + 1],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($this->merchant)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $halfDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(Order::first()->status, 3);
    }

    public function test_pay_order_with_note()
    {
        $remaining = $this->makeOrder();
        $order = Order::first();
        $note = 'payment note';
        $this->actingAs($this->merchant)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $remaining,
            'note' => $note
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  3);
        $this->assertEquals($order->fresh()->merchantPayments->first()->pivot->note,  $note);
    }
}
