<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\AItem;
use App\Models\Product;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picture;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $filters = request()->validate([
            'status' => ['sometimes', 'required'],
            'from' => ['date'],
            'to' => ['date'],
            'search' => ['sometimes', 'required'],
        ]);
        $query =  Order::query()->filter($filters);

        $orders = $query
            ->with(['purchases'])
            ->orderBy('id', 'desc')
            ->paginate(request()->per_page ?? 20);


        return response()->json([
            'data' => $orders,
            'total' => $query->sum('paid')
        ]);
    }

    public function purchase(Order $order)
    {
        abort_if(
            in_array($order->value, [OrderStatus::COMPLETED->value, OrderStatus::CANCELED->value]),
            ResponseStatus::BAD_REQUEST->value,
            'Cannot purchase a canceled or completed order'
        );
        $attributes = request()->validate([
            'price' => ['required', 'gt:0', 'numeric'],
            'quantity' => ['required', 'gt:0', 'numeric'],
            'name' => ['required']
        ]);

        $order->purchases()->create($attributes);
        return response()->json([
            'order' => $order->load(['purchases', 'aItems'])
        ]);
    }

    public function updateCustomer(Order $order)
    {
        if ($order->items->count() > 1)  $data = request()->validate([
            'customer' => ['required'],
            'phone' => ['required'],
            'address' => ['required'],
            'note' => ['required'],
        ]);
        else $data = request()->validate([
            'customer' => ['sometimes'],
            'phone' => ['sometimes'],
            'address' => ['sometimes'],
            'note' => ['sometimes'],
        ]);

        $data['updated_by'] = request()->user()->id;

        $order->update($data);
        return response()->json([
            'order' => $order
        ]);
    }

    public function status()
    {
        return response()->json(['status' => OrderStatus::array()]);
    }

    /**
     * Pay the order
     *
     * @return \Inertia\Response
     */
    public function pay(Order $order)
    {
        if (in_array($order->status, [
            OrderStatus::CANCELED->value,
            OrderStatus::PAID->value,
            OrderStatus::COMPLETED->value
        ])) {
            $message = 'Order cannot be paid anymore';
            if (request()->wantsJson()) abort(ResponseStatus::BAD_REQUEST->value, $message);
            return Redirect::back()->with('message', $message);
        }

        $totalPaid = $order->paidAmount() + $order->discount;
        $remaining = floor($order->amount - $totalPaid);
        $attributes = request()->validate([
            'payment_id' => ['required', Rule::exists('payments', 'id')],
            'amount' => [
                'required', 'numeric', function ($attribute, $value, $fail) use ($remaining) {
                    if (strval($value) <= 0) {
                        $fail('The ' . $attribute . ' must be greater than zero.');
                    }
                    if (strval($value) > strval($remaining)) {
                        $fail("The $attribute $value is greater than the remaining order amount $remaining.");
                    }
                }
            ],
            'note' => ['sometimes', 'required'],
            'picture' => ['sometimes', 'required', 'image']
        ]);

        DB::beginTransaction();
        try {
            $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'order_payments') : null;
            $payment = Payment::find($attributes['payment_id']);
            $order->payments()->attach(
                $attributes['payment_id'],
                [
                    'amount' => $attributes['amount'],
                    'number' => $payment->number,
                    'note' => $attributes['note'] ?? null,
                    'account_name' => $payment->account_name,
                    'payment_name' => DB::table('payment_types')->where('id', $payment->payment_type_id)->first()->name,
                    'picture' => $picture
                ]
            );
            if (
                $attributes['amount'] + $totalPaid < $order->amount
            ) $order->status = 2;
            else if ($attributes['amount'] + $totalPaid == $order->amount)
                $order->status = 3;
            $order->updated_by = request()->user()->id;
            $order->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($picture)
                Picture::deletePictureFromDisk($picture, 'payments');
            throw $th;
        }
        DB::commit();
        if (request()->wantsJson()) {
            $order->load(['products', 'payments', 'items', 'services']);
            Payment::generatePaymentScreenshotUrl($order);
            return response()->json(['order' => $order]);
        }
        return Redirect::back()->with('message', 'Success');
    }

    public function cancel(Order $order)
    {
        if (!$order->cancel()) return response()->json(['message', 'Order cannot be canceled']);
        if (request()->wantsJson()) return response()->json(['order' => $order->load([
            'services', 'products', 'payments', 'items'
        ])]);
        return Redirect::back()->with('message', 'Success');
    }

    public function complete(Order $order)
    {
        if (in_array($order->status, [
            OrderStatus::PAID->value,
            OrderStatus::PACKED->value
        ])) {
            DB::transaction(function () use ($order) {
                $order->status = OrderStatus::COMPLETED->value;
                $order->updated_by = request()->user()->id;
                $order->save();
            });
        } else abort(ResponseStatus::BAD_REQUEST->value, 'Order has not been fully paid');
        if (request()->wantsJson()) return response()->json(['order' => $order
            ->load(['products', 'payments', 'items', 'services'])]);
        return Redirect::back()->with('message', 'Success');
    }

    public function pack(Order $order)
    {
        if (in_array($order->status, [
            OrderStatus::PAID->value,
        ])) {
            DB::transaction(function () use ($order) {
                $order->status = OrderStatus::PACKED->value;
                $order->updated_by = request()->user()->id;
                $order->save();
            });
        } else abort(ResponseStatus::BAD_REQUEST->value, 'Order has not been fully paid');
        if (request()->wantsJson()) return response()->json(['order' => $order
            ->load(['products', 'payments', 'items', 'services'])]);
        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $search = request()->get('search');
        $serviceSearch = request()->get('serviceSearch');
        $query = Item::with(['latestProduct'])->take(5);
        $items = $search ? $query->filter(['search' => $search])->get() : $query->get();

        $query = Service::take(5);
        $services = $serviceSearch ? $query->where('name', 'like', '%' . $serviceSearch . '%')->get() : $query->get();
        return Inertia::render('PreOrder', [
            'items' => $items,
            'services' => $services,
            'search' => $search,
            'serviceSearch' => $serviceSearch
        ]);
    }

    public function preOrder()
    {
        $attributes = request()->validate([
            'customer' => ['required'],
            'phone' => ['required'],
            'address' => ['required'],
            'discount' => ['sometimes', 'required', 'numeric'],
            'note' => ['sometimes', 'required'],
            'items' => ['required', 'array'],
            'items.*' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:items,id'],
            'items.*.price' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'services' => ['sometimes', 'required', 'array'],
            'services.*' => ['required_with:services', 'array'],
            'services.*.id' => ['required_with:services', 'exists:services,id', 'distinct'],
            'services.*.quantity' => ['required_with:services', 'numeric', 'gt:0'],
            'services.*.discount' => ['sometimes', 'required', 'numeric'],
        ]);

        $items = Item::whereIn('id', array_map(fn ($val) => $val['id'], $attributes['items']))->get();
        $order = DB::transaction(function () use ($attributes, $items) {
            $amount = array_reduce($attributes['items'], fn ($carry, $val) => $carry + $val['price'] * $val['quantity'], 0);

            if (array_key_exists('services', $attributes)) {
                $services = Service::query()->whereIn('id', array_map(fn ($val) => $val['id'], $attributes['services']))->get(['id', 'price', 'name', 'cost']);

                $amount += array_reduce($attributes['services'], fn ($carry, $val) => $carry + ($services->first(fn ($v) => $v->id == $val['id'])->price) * $val['quantity'], 0);
            };

            $attributes['amount'] = $amount;
            $attributes['updated_by'] = request()->user()->id;
            $attributes['user_id'] = request()->user()->id;


            $order = Order::create(collect($attributes)->except('items', 'services')->toArray());
            foreach ($attributes['items'] as $item) {
                $order->items()->attach($item['id'], [
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'name' => $items->first(fn ($val) => $val->id == $item['id'])->id
                ]);
            }
            if (array_key_exists('services', $attributes))
                foreach ($attributes['services'] as $service) {
                    $order_service_data = [
                        'price' => $services->first(fn ($v) => $v->id == $service['id'])->price,
                        'quantity' => $service['quantity'],
                        'name' => $services->first(fn ($val) => $val->id == $service['id'])->name,
                        'cost' => $services->first(fn ($val) => $val->id == $service['id'])->cost
                    ];
                    $order->services()->attach(
                        $service['id'],
                        $order_service_data
                    );
                }
            return $order;
        });

        if (request()->wantsJson()) return response()->json(['order' => $order]);

        return Redirect::route('orders.show', ['order' => $order->id])->with('message', 'success');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderRequest $request)
    {
        $attributes = $request->validated();
        $amount = 0;
        if (array_key_exists('products', $attributes)) {
            $outOfStock = Product::outOfStock($attributes['products']);
            if ($outOfStock) return Redirect::back()->with('message', $outOfStock);

            $attributes['products'] = Product::mapForOrder($attributes['products']);

            $amount += floor((float) collect($attributes['products'])->reduce(
                fn ($carry, $product) => $carry + ($product['price'] - ($product['discount'] ?? 0)) * $product['quantity']
            ));
        }

        if (array_key_exists('services', $attributes)) {
            $attributes['services'] = Service::mapForOrder($attributes['services']);

            $amount += floor((float) collect($attributes['services'])->reduce(
                fn ($carry, $service) => $carry + ($service['price'] - ($service['discount'] ?? 0)) * $service['quantity'],
                0
            ));
        }

        $remaining = $amount - floor($attributes['discount'] ?? 0);

        if ($remaining < 0) return Redirect::back()->with('message', 'Total discount is greater than the amount');

        $attributes['updated_by'] = $request->user()->id;
        $attributes['user_id'] = $request->user()->id;
        $createdOrder = DB::transaction(function () use ($attributes, $amount, $remaining) {
            $order = Order::create(
                collect([
                    'amount' => $amount,
                    ...$attributes
                ])->except(['products', 'services'])->toArray()
            );
            if ($remaining == 0) $order->update(['status' => 3]);

            if (array_key_exists('products', $attributes)) {
                $order->products()->attach(
                    collect($attributes['products'])->mapWithKeys(fn ($product) => [$product['id'] => [
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'discount' => $product['discount'] ?? 0,
                        'name' => $product['name'],
                        'purchase_price' => $product['purchase_price']
                    ]])->toArray()
                );
            }


            if (array_key_exists('services', $attributes)) {
                $order->services()->attach(
                    collect($attributes['services'])->mapWithKeys(fn ($service) => [
                        $service['id'] => [
                            'quantity' => $service['quantity'],
                            'price' => $service['price'],
                            'cost' => $service['cost'],
                            'discount' => $service['discount'] ?? 0,
                            'name' => $service['name']
                        ]
                    ])->toArray()
                );
            }

            if (array_key_exists('products', $attributes)) {
                $order->products->each(function ($product) {
                    $product->stock -= $product->pivot->quantity;
                    $product->save();
                    $quantity = $product->pivot->quantity;
                    $product->batches()
                        ->where('stock', '>', 0)
                        ->get()
                        ->each(function ($batch) use (&$quantity, $product) {
                            if ($quantity > 0) {
                                $product->pivot->batches()->attach(
                                    $batch->id,
                                    [
                                        'quantity' => $batch->stock < $quantity ? $batch->stock : $quantity
                                    ]
                                );
                                if ($batch->stock < $quantity) {
                                    $batch->stock = 0;
                                    $batch->save();
                                    $quantity -= $batch->stock;
                                } else {
                                    $batch->stock -= $quantity;
                                    $batch->save();
                                    $quantity = 0;
                                }
                            }
                        });
                });
            }
            return $order;
        });
        if ($request->wantsJson()) return response()->json([
            'order' => $createdOrder
        ]);
        return Redirect::route('orders.show', ['order' => $createdOrder->id])->with('message', 'Success');
    }

    public function record(Order $order = null)
    {
        $attributes = request()->validate([
            'customer' => [''],
            'phone' => [''],
            'address' => ['max:255'],
            'note' => ['max:255'],
            'created_at' => ['sometimes', 'date'],
            'discount' => ['sometimes', 'numeric'],
            'paid' => ['sometimes', 'numeric'],
            'a_items' => ['required', 'array'],
            'a_items.*' => ['required', 'array'],
            'a_items.*.id' => ['required', 'exists:a_items,id'],
            'a_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'a_items.*.discount' => ['sometimes', 'required', 'numeric'],
            'status' => [Rule::in([
                OrderStatus::PACKED->value,
                OrderStatus::CANCELED->value,
                OrderStatus::COMPLETED->value,
                OrderStatus::ON_DELIVERY->value,
            ])]
        ]);

        abort_if(
            $order != null &&
                in_array($order->status, [
                    OrderStatus::CANCELED->value,
                    OrderStatus::COMPLETED->value,
                ]),
            ResponseStatus::BAD_REQUEST->value,
            'Order is already completed or canceled'
        );

        if (array_key_exists('status', $attributes)) {
            abort_if(
                $order == null,
                ResponseStatus::BAD_REQUEST->value,
                'Order does not exist'
            );

            abort_if(
                $attributes['status'] == OrderStatus::PACKED->value &&
                    $order->status != OrderStatus::PAID->value,
                ResponseStatus::BAD_REQUEST->value,
                'Can only pack a paid order'
            );

            abort_if(
                $attributes['status'] == OrderStatus::ON_DELIVERY->value &&
                    $order->status != OrderStatus::PACKED->value,
                ResponseStatus::BAD_REQUEST->value,
                'Can only deliver a packed order'
            );

            abort_if(
                $attributes['status'] == OrderStatus::COMPLETED->value &&
                    in_array($order->status, [
                        OrderStatus::CANCELED->value,
                        OrderStatus::PARTIALLY_PAID->value,
                        OrderStatus::PENDING->value
                    ]),
                ResponseStatus::BAD_REQUEST->value,
                'Cannot complete the order yet'
            );

            abort_if(
                $attributes['status'] == OrderStatus::CANCELED->value &&
                    $order->status == OrderStatus::COMPLETED->value,
                ResponseStatus::BAD_REQUEST->value,
                'Cannot cancel a completed order'
            );

            $order->update(['status' => $attributes['status']]);
            if ($attributes['status'] == OrderStatus::CANCELED->value) {
                $order->update([
                    'paid' => 0
                ]);
                $order->purchases()->update(['status' => PurchaseStatus::CANCELED->value]);
                $order->reverseStock();
            }
            return response()->json([
                'order' => $order->load(['aItems'])
            ]);
        }

        $data = DB::transaction(function () use ($attributes, $order) {
            if ($order != null) $order->reverseStock();

            AItem::checkStock($attributes['a_items']);

            $attributes['a_items'] = AItem::mapForOrder($attributes['a_items']);

            $amount =  collect($attributes['a_items'])->reduce(
                fn ($carry, $product) => $carry + ($product['price'] - ($product['discount'] ?? 0)) * $product['quantity']
            );

            $remaining = $amount - ($attributes['discount'] ?? 0) - ($attributes['paid'] ?? 0);

            abort_if($remaining < 0, ResponseStatus::BAD_REQUEST->value, 'Cannot pay(or discount) more than the order amount');

            $attributes['updated_by'] = request()->user()->id;
            $attributes['user_id'] = request()->user()->id;
            $createdAt = new Carbon($attributes['created_at']);
            $now = now();
            $createdAt->addHour($now->hour);
            $createdAt->addMinute($now->minute);
            $createdAt->second($now->second);
            $attributes['created_at'] = $createdAt;


            $status = OrderStatus::PENDING->value;
            if ($remaining == 0) $status = OrderStatus::PAID->value;
            else if ($attributes['paid'] ?? false) $status = OrderStatus::PARTIALLY_PAID->value;

            $orderData = collect([
                'amount' => $amount,
                'status' => $status,
                ...$attributes
            ])->except(['a_items'])->toArray();
            if ($order == null) $order = Order::create($orderData);
            else $order->update($orderData);
            DB::table('a_item_order')->insert(array_map(function ($aItem) use ($order) {
                return [
                    'a_item_id' => $aItem['id'],
                    'order_id' => $order->id,
                    'quantity' => $aItem['quantity'],
                    'price' => $aItem['price'],
                    'discount' => $aItem['discount'] ?? 0,
                    'name' => $aItem['name'],
                    'purchase_price' => $aItem['purchase_price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $attributes['a_items']));

            $order->fresh()->aItems->each(function ($item) {
                DB::table('a_items')->where([
                    'id' => $item->id,
                    'type' => ProductType::STOCKED->value
                ])->decrement('stock', $item->pivot->quantity);
            });
            return $order->load(['aItems', 'purchases']);
        });
        return response()->json([
            'order' => $data
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $order->load(['aItems', 'purchases']);
        Payment::generatePaymentScreenshotUrl($order);
        if (request()->wantsJson()) return response()->json([
            'order' => $order
        ]);
        return Inertia::render('Order', [
            'order' => $order,
            'payments' => Payment::where('status', PaymentStatus::ENABLED->value)->get(),
            'payment_types' => DB::table('payment_types')->get(),
            'order_statuses' => OrderStatus::array()
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOrderRequest  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
