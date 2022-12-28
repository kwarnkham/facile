<?php

namespace Tests\Feature;

use App\Enums\FeatureType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Models\Batch;
use App\Models\Credit;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use App\Models\Picture;
use App\Models\Purchase;
use App\Models\Topping;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    protected $item;

    public function setUp(): void
    {
        parent::setUp();
        $this->item = Item::factory()->create();
    }

    public function makeFeature(array $data)
    {
        $data['item_id'] = $this->item->id;
        $feature = Feature::create($data);

        $purchase = $feature->purchases()->create(['quantity' => $feature->stock, 'price' => $feature->price * 0.9]);

        $feature->batches()->create(['purchase_id' => $purchase->id, 'stock' => $feature->stock]);

        return $feature;
    }

    public function featureAmount(array $features)
    {
        return array_reduce($features, function ($carry, $dataFeature) {
            $feature = Feature::find($dataFeature['id']);
            return $carry + (($feature->price - ($dataFeature['discount'] ?? 0)) * $dataFeature['quantity']);
        }, 0);
    }

    public function toppingAmount(array $toppings)
    {
        return array_reduce($toppings, function ($carry, $dataTopping) {
            $topping = Topping::find($dataTopping['id']);
            return $carry + $topping->price  * $dataTopping['quantity'];
        }, 0);
    }

    public function makeOrder(Collection $features, $discountFactor = 0, $featureDisountFactor = 0, Collection $toppings = null)
    {
        $features = $features->map(function ($dataFeature) {
            return $this->makeFeature($dataFeature->toArray());
        });

        $dataFeatures = $features->map(function ($feature) use ($featureDisountFactor) {
            $dataFeature = [
                'id' => $feature->id,
                'quantity' => $feature->stock,
            ];
            if ($featureDisountFactor) $dataFeature['discount'] = floor($feature->price * $featureDisountFactor);
            return $dataFeature;
        })->toArray();

        $data = [
            ...['features' => $dataFeatures],
            ...Order::factory()->make()->toArray()
        ];

        if ($toppings) {
            $data['toppings'] = $toppings->map(fn ($topping) => [
                'id' => $topping->id,
                'quantity' => $topping->quantity ?? rand(1, 10)
            ])->toArray();
        }

        if ($discountFactor) $data['discount'] = floor($this->featureAmount($dataFeatures) * $discountFactor);
        $this->actingAs($this->user)->post(route('orders.store'), $data);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(floor(Order::first()->amount), floor(

            $this->featureAmount($dataFeatures) + (array_key_exists('toppings', $data) ? $this->toppingAmount($data['toppings']) : 0)
        ));
        return [
            'amount' => $this->featureAmount($dataFeatures)
        ];
    }

    public function test_create_order()
    {
        $this->makeOrder(Feature::factory(2)->make());
    }

    public function test_cancelling_unstocked_order_does_not_refill_stocks()
    {
        $features = Feature::factory(rand(2, 10))->make([
            'type' => FeatureType::UNSTOCKED->value
        ]);
        $madeOrder = $this->makeOrder($features);
        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);

        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]));

        $order->features->each(function ($feature) {
            $this->assertEquals($feature->fresh()->stock, 0);
        });
    }

    public function test_create_order_with_toppings()
    {
        $toppings = Topping::factory(3)->create()->map(function ($topping) {
            $topping->quantity = rand(1, 10);
            return $topping;
        });

        $features = Feature::factory(rand(2, 10))->make();

        $this->makeOrder(toppings: $toppings, features: $features);
        $this->assertDatabaseCount('order_topping', $toppings->count());
        $toppings->each(fn ($topping) => $this->assertDatabaseHas('order_topping', [
            'id' => $topping->id,
            'price' => $topping->price,
            'quantity' => $topping->quantity,
        ]));
    }

    public function test_cancelling_paid_order_generate_credit()
    {
        $features = Feature::factory(rand(2, 10))->make();
        $madeOrder = $this->makeOrder($features);
        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount']
        ]);

        $this->assertDatabaseCount('order_payment', 1);

        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]));

        $this->assertDatabaseCount('credits', 1);
        $this->assertEquals(Credit::first()->amount, $madeOrder['amount']);
    }

    public function test_cannot_cancel_a_completed_order_after_24_hours()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount']
        ]);

        $time = (clone $order->updated_at)->addHours(25);
        $this->travelTo($time);
        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]))->assertSessionHas('message', 'Cannot cancel a paid order after 24 hours');
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);
    }

    public function test_complete_an_order()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PARTIALLY_PAID->value);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'] - floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);

        $this->actingAs($this->user)->post(route('orders.complete', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::COMPLETED->value);
    }

    public function test_pay_order_with_picture()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 5),
            'picture' => UploadedFile::fake()->image('screenshot.jpg')
        ]);

        $picture = $order->payments()->first()->pivot->picture;
        $this->assertTrue(Storage::exists(Picture::picturePath($picture, 'order_payments')));
        $this->assertTrue(Picture::deletePictureFromDisk($picture, 'order_payments'));

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 5),
            'picture' => 'picture.jpg'
        ])->assertSessionHasErrors(['picture']);
    }

    public function test_batch_is_reduced_with_order_created()
    {
        $this->makeOrder(Feature::factory(2)->make());
        $this->assertDatabaseCount('batches', 2);
        Batch::all()->each(function ($batch) {
            $this->assertEquals($batch->stock, 0);
        });
    }

    public function test_batch_is_restocked_with_order_canceled()
    {
        $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount,
        ]);

        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]));

        $this->assertDatabaseCount('batches', 2);
        Batch::all()->each(function ($batch) {
            $this->assertEquals($batch->stock, Purchase::find($batch->purchase_id)->quantity);
        });
    }


    public function test_stock_is_restocked_when_order_is_canceled()
    {
        $stock = rand(20, 30);
        $this->makeOrder(Feature::factory(2)->make(['stock' => $stock]));
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]));
        $this->assertEquals(OrderStatus::CANCELED->value, $order->fresh()->status);

        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, $stock));
    }

    public function test_create_an_order_with_order_discount_only()
    {
        $dateFeatures = Feature::factory(2)->make();
        $this->makeOrder($dateFeatures, 0.5);
        $order = Order::first();
        $this->assertEquals(floor(Order::first()->amount * 0.5), $order->discount);
    }

    public function test_out_of_stock_feature_cannot_be_created_for_order()
    {
        $this->makeOrder(Feature::factory(2)->make());
        $features = Feature::all();

        $this->actingAs($this->user)->post(route('orders.store'), [
            ...[
                'features' => $features->map(fn ($feature) => ['id' => $feature->id, 'quantity' => 1])->toArray(),
            ],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $features->each(fn ($feature) => $this->assertEquals($feature->stock, 0));
    }

    public function test_stock_is_reduced_properly()
    {
        $this->makeOrder(Feature::factory(2)->make());
        Feature::all()->each(fn ($feature) => $this->assertEquals($feature->stock, 0));
    }

    public function test_create_an_order_with_full_order_discount()
    {
        $dataFeature = Feature::factory(2)->make();
        $this->makeOrder($dataFeature, 1);
        $order = Order::first();
        $this->assertEquals($order->status, 3);
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => 0
        ])->assertStatus(ResponseStatus::REDIRECTED_BACK->value)->assertSessionHas('message', 'Order cannot be paid anymore');
    }

    public function test_create_an_order_with_order_discount_and_feature_discount()
    {
        $dataFeature = Feature::factory(2)->make();
        $this->makeOrder($dataFeature, 0.5, 0.3);
        $order = Order::first();
        $this->assertEquals(floor($order->amount * 0.5), floor($order->discount));
        $this->assertEquals(
            $order->amount,
            (int)$order->features->reduce(
                fn ($carry, $feature) => ($feature->price - floor($feature->price * 0.3)) * $feature->pivot->quantity + $carry,
                0
            )
        );
    }

    public function test_features_in_order_must_be_distinct()
    {
        $item = Item::factory()->create();
        $stock = rand(2, 14);
        $features = Feature::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($feature) =>
            ['id' => $feature->id, 'quantity' => $stock]
        )->toArray();
        $features_b = $features;
        $this->actingAs($this->user)->post(route('orders.store'), [
            ...['features' => [...$features, ...$features_b]],
            ...Order::factory()->make()->toArray()
        ])->assertSessionHasErrors(['features.0.id']);
    }

    public function test_pay_order_using_payment()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 4)
        ]);
        $this->assertDatabaseCount('order_payment', 2);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'] - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);
        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status,  3);
    }

    public function test_pay_order_fully()
    {
        $this->makeOrder(Feature::factory(2)->make());

        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_cannot_pay_more_than_order_amount()
    {
        $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 2)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 4)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount * 2
        ])->assertSessionHasErrors(['amount']);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);

        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_with_discount()
    {
        $this->makeOrder(Feature::factory(2)->make(), 0.5);
        $order = Order::first();
        $this->assertEquals(floor($order->amount * 0.5), $order->discount);

        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - $order->discount
        ]);

        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_batch_stock_is_reduced_from_expired_on_order()
    {
        $dataFeature = Feature::factory()->make([
            'item_id' => Item::factory()->create()->id
        ])->toArray();
        $this->actingAs($this->user)->post(route('features.store'), [
            ...$dataFeature,
            ...['purchase_price' => floor($dataFeature['price'] * 0.9), 'expired_on' => now()->addDays(10)]
        ]);

        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('purchases', 1);

        $feature = Feature::first();
        $this->actingAs($this->user)->post(route('features.restock', ['feature' => $feature->id]), [
            'price' => $feature->price,
            'quantity' => 50,
            'expired_on' => now()->addDays(5)
        ]);

        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseCount('batches', 2);
        $this->assertDatabaseCount('purchases', 2);

        $this->actingAs($this->user)->post(route('features.restock', ['feature' => $feature->id]), [
            'price' => $feature->price,
            'quantity' => 20,
            'expired_on' => now()->addDays(20)
        ]);

        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseCount('batches', 3);
        $this->assertDatabaseCount('purchases', 3);

        $dataFeatures = Feature::all()->map(function ($feature) {
            return [
                'id' => $feature->id,
                'quantity' => 50
            ];
        })->toArray();

        $this->actingAs($this->user)->post(route('orders.store'), [
            ...Order::factory()->make()->toArray(),
            ...['features' => $dataFeatures]
        ]);
        $this->assertDatabaseCount('orders', 1);

        $this->assertEquals(Purchase::where('quantity', 50)->first()->id, Batch::where('stock', 0)->first()->purchase_id);

        $this->actingAs($this->user)->post(route('features.restock', ['feature' => $feature->id]), [
            'price' => $feature->price,
            'quantity' => 10,
            'expired_on' => now()->addDays(4)
        ]);

        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseCount('batches', 4);
        $this->assertDatabaseCount('purchases', 4);
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);

        $this->actingAs($this->user)->post(route('orders.cancel', ['order' => $order->id]));
        $this->assertDatabaseCount('credits', 1);

        $this->assertEquals(Purchase::where('quantity', 50)->first()->id, Batch::where('stock', 50)->first()->purchase_id);
    }

    public function test_pay_order_that_has_discount_features()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make(), featureDisountFactor: 0.2);
        $order = Order::first();
        if ($order->status != 3) {
            $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->payment->id,
                'amount' => floor($madeOrder['amount']  / 2)
            ]);

            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->payment->id,
                'amount' => $madeOrder['amount'] - floor($madeOrder['amount'] / 2)
            ]);

            $this->assertDatabaseCount('order_payment', 2);
            $this->assertEquals($order->fresh()->status, 3);
        }
    }

    public function test_all_discount_cannot_be_greater_than_order_amount()
    {
        $item = Item::factory()->create();
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

        foreach ($features as $v) {
            $feature = Feature::find($v['id']);
            $purchase = $feature->purchases()->create([
                'price' => $feature->price * 0.9,
                'quantity' => $feature->stock
            ]);
            $feature->batches()->create([
                'purchase_id' => $purchase->id,
                'stock' => $feature->stock,
            ]);
        }

        $halfDiscount = (float)(Feature::all()->reduce(
            fn ($carry, $v) => $v->price * $discount * collect($features)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry,
            0
        ));

        $this->actingAs($this->user)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $halfDiscount + 1],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($this->user)->post(route('orders.store'), [
            ...['features' => $features, 'discount' => $halfDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(Order::first()->status, 3);
    }

    public function test_pay_order_with_note()
    {
        $madeOrder = $this->makeOrder(Feature::factory(2)->make());
        $order = Order::first();
        $note = 'payment note';
        $this->actingAs($this->user)->post(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'],
            'note' => $note
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  3);
        $this->assertEquals($order->fresh()->payments->first()->pivot->note,  $note);
    }

    public function test_purchase_can_be_canceled_only_if_no_non_canceled_order_associted()
    {
        $dataFeature = Feature::factory()->make(['item_id' => $this->item->id]);
        $this->actingAs($this->user)->post(route('features.store'), [
            ...$dataFeature->toArray(),
            'purchase_price' => floor($dataFeature->price * 0.5),
            'expired_on' => now()->addDays(10)
        ]);

        $this->assertDatabaseCount('features', 1);

        $this->actingAs($this->user)->post(route('orders.store'), [
            ...Order::factory()->make()->toArray(),
            'features' => Feature::all()->map(fn ($feature) => [
                'id' => $feature->id,
                'quantity' => $feature->stock
            ])->toArray()
        ]);
        $this->assertDatabaseCount('orders', 1);

        $purchase = Purchase::first();
        $this->actingAs($this->user)->post(route('purchases.cancel', [
            'purchase' => $purchase->id
        ]));

        $this->assertEquals($purchase->fresh()->status, PurchaseStatus::NORMAL->value);
        $order = Order::first();
        $this->actingAs($this->user)->post(route('orders.cancel', [
            'order' => $order->id
        ]));
        $this->assertEquals($order->fresh()->status, OrderStatus::CANCELED->value);

        $this->actingAs($this->user)->post(route('purchases.cancel', [
            'purchase' => $purchase->id
        ]));

        $this->assertEquals($purchase->fresh()->status, PurchaseStatus::CANCELED->value);
        $this->assertEquals(Feature::first()->stock, 0);
        $this->assertEquals(Batch::first()->stock, 0);
    }
}
