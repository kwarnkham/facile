<?php

namespace Tests\Product;

use App\Enums\ProductType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Models\Batch;
use App\Models\Item;
use App\Models\Order;
use App\Models\Picture;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    public function makeProduct(array $data)
    {
        $data['item_id'] = $this->item->id;
        $product = Product::create($data);

        $purchase = $product->purchases()->create([
            'quantity' => $product->stock,
            'price' => $product->price * 0.9,
            'name' => $product->name
        ]);

        $product->batches()->create(['purchase_id' => $purchase->id, 'stock' => $product->stock]);

        return $product;
    }

    public function productAmount(array $products)
    {
        return array_reduce($products, function ($carry, $dataProduct) {
            $product = Product::find($dataProduct['id']);
            return $carry + (($product->price - ($dataProduct['discount'] ?? 0)) * $dataProduct['quantity']);
        }, 0);
    }

    public function serviceAmount(array $services)
    {
        return array_reduce($services, function ($carry, $dataService) {
            $service = Service::find($dataService['id']);
            return ($carry + ($service->price - ($dataService['discount'] ?? 0))  * $dataService['quantity']);
        }, 0);
    }

    public function makeOrder(Collection $products, $discountFactor = 0, $productDiscountFactor = 0, Collection $services = null)
    {
        $products = $products->map(function ($dataProduct) {
            return $this->makeProduct($dataProduct->toArray());
        });

        $dataProducts = $products->map(function ($product) use ($productDiscountFactor) {
            $dataProduct = [
                'id' => $product->id,
                'quantity' => $product->stock,
            ];
            if ($productDiscountFactor) $dataProduct['discount'] = floor($product->price * $productDiscountFactor);
            return $dataProduct;
        })->toArray();

        $data = [
            ...['products' => $dataProducts],
            ...Order::factory()->make()->toArray()
        ];

        if ($services) {
            $data['services'] = $services->map(function ($service) {
                $temp = [
                    'id' => $service->id,
                    'quantity' => $service->quantity ?? rand(1, 10),
                ];
                if ($service->discount) $temp['discount'] = $service->discount;
                return $temp;
            })->toArray();
        }

        if ($discountFactor) $data['discount'] = floor($this->productAmount($dataProducts) * $discountFactor);
        $this->actingAs($this->user)->postJson(route('orders.store'), $data);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(floor(Order::first()->amount), floor(
            $this->productAmount($dataProducts) + (array_key_exists('services', $data) ? $this->serviceAmount($data['services']) : 0)
        ));
        return [
            'amount' => $this->productAmount($dataProducts)
        ];
    }

    public function test_create_order()
    {
        $this->makeOrder(Product::factory(2)->make());
    }

    public function test_cancelling_unstocked_order_does_not_refill_stocks()
    {
        $products = Product::factory(rand(2, 10))->make([
            'type' => ProductType::UNSTOCKED->value
        ]);
        $madeOrder = $this->makeOrder($products);
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);

        $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]));

        $order->products->each(function ($product) {
            $this->assertEquals($product->fresh()->stock, 0);
        });
    }

    public function test_create_order_with_services()
    {
        $services = Service::factory(3)->create()->map(function ($service) {
            $service->quantity = rand(1, 10);
            return $service;
        });

        $products = Product::factory(rand(2, 10))->make();

        $this->makeOrder(services: $services, products: $products);
        $this->assertDatabaseCount('order_service', $services->count());
    }

    public function test_create_order_with_services_that_have_discount()
    {
        $services = Service::factory(3)->create()->map(function ($service) {
            $service->quantity = rand(1, 10);
            $service->discount = floor($service->price / 2);
            return $service;
        });

        $products = Product::factory(rand(2, 10))->make();

        $this->makeOrder(services: $services, products: $products);
        $this->assertDatabaseCount('order_service', $services->count());
    }

    public function test_cancelling_paid_order()
    {
        $products = Product::factory(rand(2, 10))->make();
        $madeOrder = $this->makeOrder($products);
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount']
        ]);

        $this->assertDatabaseCount('order_payment', 1);

        $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]));

        $paid = DB::table('order_payment')
            ->join('orders', 'orders.id', '=', 'order_payment.order_id')
            ->where('orders.status', '=', OrderStatus::CANCELED->value)
            ->get(['orders.amount'])->reduce(fn ($carry, $val) => $carry + $val->amount, 0);

        $this->assertEquals(
            $madeOrder['amount'],
            $paid
        );
    }

    // public function test_cannot_cancel_a_completed_order_after_24_hours()
    // {
    //     $madeOrder = $this->makeOrder(Product::factory(2)->make());
    //     $order = Order::first();
    //     $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
    //         'payment_id' => $this->payment->id,
    //         'amount' => $madeOrder['amount']
    //     ]);

    //     $time = (clone $order->updated_at)->addHours(25);
    //     $this->travelTo($time);
    //     $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]))->assertSessionHas('message', 'Cannot cancel a paid order after 24 hours');
    //     $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);
    // }

    public function test_pack_an_order()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PARTIALLY_PAID->value);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'] - floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);

        $this->actingAs($this->user)->postJson(route('orders.pack', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::PACKED->value);
    }

    public function test_complete_an_order()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PARTIALLY_PAID->value);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'] - floor($madeOrder['amount'] / 2)
        ]);
        $this->assertEquals($order->fresh()->status, OrderStatus::PAID->value);

        $this->actingAs($this->user)->postJson(route('orders.complete', ['order' => $order->id]));
        $this->assertEquals($order->fresh()->status, OrderStatus::COMPLETED->value);
    }

    public function test_pay_order_with_picture()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();
        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 5),
            'picture' => UploadedFile::fake()->image('screenshot.jpg')
        ]);

        $picture = $order->payments()->first()->pivot->picture;
        $this->assertTrue(Storage::exists(Picture::picturePath($picture, 'order_payments')));
        $this->assertTrue(Picture::deletePictureFromDisk($picture, 'order_payments'));

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 5),
            'picture' => 'picture.jpg'
        ])->assertUnprocessable();
    }

    public function test_batch_is_reduced_with_order_created()
    {
        $this->makeOrder(Product::factory(2)->make());
        $this->assertDatabaseCount('batches', 2);
        Batch::all()->each(function ($batch) {
            $this->assertEquals($batch->stock, 0);
        });
    }

    public function test_batch_is_restocked_with_order_canceled()
    {
        $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();
        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount,
        ]);

        $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]));

        $this->assertDatabaseCount('batches', 2);
        // Batch::all()->each(function ($batch) {
        //     $this->assertEquals($batch->stock, Purchase::find($batch->purchase_id)->quantity);
        // });
    }


    public function test_stock_is_restocked_when_order_is_canceled()
    {
        $stock = rand(20, 30);
        $this->makeOrder(Product::factory(2)->make(['stock' => $stock]));
        $order = Order::first();
        $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]));
        $this->assertEquals(OrderStatus::CANCELED->value, $order->fresh()->status);

        Product::all()->each(fn ($product) => $this->assertEquals($product->stock, $stock));
    }

    public function test_create_an_order_with_order_discount_only()
    {
        $dataProducts = Product::factory(2)->make();
        $this->makeOrder($dataProducts, 0.5);
        $order = Order::first();
        $this->assertEquals(floor(Order::first()->amount * 0.5), $order->discount);
    }

    public function test_out_of_stock_product_cannot_be_created_for_order()
    {
        $this->makeOrder(Product::factory(2)->make());
        $products = Product::all();
        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...[
                'products' => $products->map(fn ($product) => ['id' => $product->id, 'quantity' => 1])->toArray(),
            ],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $products->each(fn ($product) => $this->assertEquals($product->stock, 0));
    }

    public function test_stock_is_reduced_properly()
    {
        $this->makeOrder(Product::factory(2)->make());
        Product::all()->each(fn ($product) => $this->assertEquals($product->stock, 0));
    }

    public function test_create_an_order_with_full_order_discount()
    {
        $dataProduct = Product::factory(2)->make();
        $this->makeOrder($dataProduct, 1);
        $order = Order::first();
        $this->assertEquals($order->status, OrderStatus::PAID->value);
        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => 0
        ])->assertStatus(ResponseStatus::BAD_REQUEST->value);
    }

    public function test_update_customer_info_of_order()
    {
        $this->makeOrder(Product::factory(2)->make());
        $data = [
            'customer' => 'updated',
            'phone' => 'updated',
            'address' => 'updated',
            'note' => 'updated',
        ];
        $this->actingAs($this->user)->put(route('orders.update.customer', ['order' => Order::first()->id]), $data);

        $this->assertDatabaseHas('orders', $data);
    }

    public function test_create_an_order_with_order_discount_and_product_discount()
    {
        $dataProduct = Product::factory(2)->make();
        $this->makeOrder($dataProduct, 0.5, 0.3);
        $order = Order::first();
        $this->assertEquals(floor($order->amount * 0.5), floor($order->discount));
        $this->assertEquals(
            $order->amount,
            (int)$order->products->reduce(
                fn ($carry, $product) => ($product->price - floor($product->price * 0.3)) * $product->pivot->quantity + $carry,
                0
            )
        );
    }

    public function test_products_in_order_must_be_distinct()
    {
        $item = Item::factory()->create();
        $stock = rand(2, 14);
        $products = Product::factory(rand(1, 10))->create(['item_id' => $item->id, 'stock' => $stock])->map(
            fn ($product) =>
            ['id' => $product->id, 'quantity' => $stock]
        )->toArray();
        $products_b = $products;
        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...['products' => [...$products, ...$products_b]],
            ...Order::factory()->make()->toArray()
        ])->assertUnprocessable();
    }

    public function test_pay_order_using_payment()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 2)
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($madeOrder['amount'] / 4)
        ]);
        $this->assertDatabaseCount('order_payment', 2);
        $this->assertEquals($order->fresh()->status,  2);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $madeOrder['amount'] - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);
        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status,  3);
    }

    public function test_pay_order_fully()
    {
        $this->makeOrder(Product::factory(2)->make());

        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_cannot_pay_more_than_order_amount()
    {
        $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 2)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => floor($order->amount / 4)
        ]);
        $this->assertEquals($order->fresh()->status, 2);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount * 2
        ])->assertUnprocessable();

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - (float)$order->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry)
        ]);

        $this->assertDatabaseCount('order_payment', 3);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_pay_order_with_discount()
    {
        $this->makeOrder(Product::factory(2)->make(), 0.5);
        $order = Order::first();
        $this->assertEquals(floor($order->amount * 0.5), $order->discount);

        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount - $order->discount
        ]);

        $this->assertDatabaseCount('order_payment', 1);
        $this->assertEquals($order->fresh()->status, 3);
    }

    public function test_batch_stock_is_reduced_from_expired_on_order()
    {
        $dataProduct = Product::factory()->make([
            'item_id' => Item::factory()->create()->id
        ])->toArray();
        $this->actingAs($this->user)->postJson(route('products.store'), [
            ...$dataProduct,
            ...['purchase_price' => floor($dataProduct['price'] * 0.9), 'expired_on' => now()->addDays(10)->format('Y-m-d')]
        ]);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 1);
        $this->assertDatabaseCount('purchases', 1);

        $product = Product::first();
        $this->actingAs($this->user)->postJson(route('products.restock', ['product' => $product->id]), [
            'price' => $product->price,
            'quantity' => 50,
            'expired_on' => now()->addDays(5)->format('Y-m-d')
        ]);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 2);
        $this->assertDatabaseCount('purchases', 2);

        $this->actingAs($this->user)->postJson(route('products.restock', ['product' => $product->id]), [
            'price' => $product->price,
            'quantity' => 20,
            'expired_on' => now()->addDays(20)->format('Y-m-d')
        ]);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 3);
        $this->assertDatabaseCount('purchases', 3);

        $dataProducts = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'quantity' => 50
            ];
        })->toArray();

        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...Order::factory()->make()->toArray(),
            ...['products' => $dataProducts]
        ]);
        $this->assertDatabaseCount('orders', 1);

        $this->assertEquals(Purchase::where('quantity', 50)->first()->id, Batch::where('stock', 0)->first()->purchase_id);

        $this->actingAs($this->user)->postJson(route('products.restock', ['product' => $product->id]), [
            'price' => $product->price,
            'quantity' => 10,
            'expired_on' => now()->addDays(4)->format('Y-m-d')
        ]);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('batches', 4);
        $this->assertDatabaseCount('purchases', 4);
        $order = Order::first();
        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
            'payment_id' => $this->payment->id,
            'amount' => $order->amount
        ]);
        $this->assertDatabaseCount('order_payment', 1);

        $this->actingAs($this->user)->postJson(route('orders.cancel', ['order' => $order->id]));

        $this->assertEquals(Purchase::where('quantity', 50)->first()->id, Batch::where('stock', 50)->first()->purchase_id);
    }

    public function test_pay_order_that_has_discount_products()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make(), productDiscountFactor: 0.2);
        $order = Order::first();
        if ($order->status != 3) {
            $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
                'payment_id' => $this->payment->id,
                'amount' => floor($madeOrder['amount']  / 2)
            ]);

            $this->assertEquals($order->fresh()->status, 2);

            $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
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
        $products = Product::factory(rand(1, 10))->create(['item_id' => $item->id, 'price' => rand(1, 100) * 10, 'stock' => $stock])->map(
            fn ($product) =>
            [
                'id' => $product->id,
                'quantity' => $stock,
                'discount' => $product->price * $discount
            ]
        )->toArray();

        foreach ($products as $v) {
            $product = Product::find($v['id']);
            $purchase = $product->purchases()->create([
                'price' => $product->price * 0.9,
                'quantity' => $product->stock,
                'name' => $product->name
            ]);
            $product->batches()->create([
                'purchase_id' => $purchase->id,
                'stock' => $product->stock,
            ]);
        }

        $halfDiscount = (float)(Product::all()->reduce(
            fn ($carry, $v) => $v->price * $discount * collect($products)->first(fn ($f) => $f['id'] == $v->id)['quantity'] + $carry,
            0
        ));

        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...['products' => $products, 'discount' => $halfDiscount + 1],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...['products' => $products, 'discount' => $halfDiscount],
            ...Order::factory()->make()->toArray()
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(Order::first()->status, 3);
    }

    public function test_pay_order_with_note()
    {
        $madeOrder = $this->makeOrder(Product::factory(2)->make());
        $order = Order::first();
        $note = 'payment note';
        $this->actingAs($this->user)->postJson(route('orders.pay', ['order' => $order->id]), [
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
        $dataProduct = Product::factory()->make(['item_id' => $this->item->id]);
        $this->actingAs($this->user)->postJson(route('products.store'), [
            ...$dataProduct->toArray(),
            'purchase_price' => floor($dataProduct->price * 0.5),
            'expired_on' => now()->addDays(10)->format('Y-m-d')
        ]);

        $this->assertDatabaseCount('products', 1);

        $this->actingAs($this->user)->postJson(route('orders.store'), [
            ...Order::factory()->make()->toArray(),
            'products' => Product::all()->map(fn ($product) => [
                'id' => $product->id,
                'quantity' => $product->stock
            ])->toArray()
        ]);
        $this->assertDatabaseCount('orders', 1);

        $purchase = Purchase::first();
        $this->actingAs($this->user)->postJson(route('purchases.cancel', [
            'purchase' => $purchase->id
        ]));

        $this->assertEquals($purchase->fresh()->status, PurchaseStatus::NORMAL->value);
        $order = Order::first();
        $this->actingAs($this->user)->postJson(route('orders.cancel', [
            'order' => $order->id
        ]));
        $this->assertEquals($order->fresh()->status, OrderStatus::CANCELED->value);

        $this->actingAs($this->user)->postJson(route('purchases.cancel', [
            'purchase' => $purchase->id
        ]));

        $this->assertEquals($purchase->fresh()->status, PurchaseStatus::CANCELED->value);
        $this->assertEquals(Product::first()->stock, 0);
        $this->assertEquals(Batch::first()->stock, 0);
    }
}
