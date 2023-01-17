<?php

namespace App\Http\Controllers;

use App\Enums\FeatureType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Credit;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picture;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
            'status' => ['in:1,2,3,4,5']
        ]);
        if (!array_key_exists('status', $filters))
            $filters['status'] = 1;
        $order = Order::where('status', $filters['status'])->paginate(request()->per_page ?? 20);
        return Inertia::render('Orders', [
            'orders' => $order,
            'order_statuses' => OrderStatus::array(),
            'filters' => $filters
        ]);
    }

    /**
     * Pay the order
     *
     * @return \Inertia\Response
     */
    public function pay(Order $order)
    {
        if ($order->status == 3) return Redirect::back()->with('message', 'Order cannot be paid anymore');
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
            $order->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($picture)
                Picture::deletePictureFromDisk($picture, 'payments');
            throw $th;
        }
        DB::commit();
        return Redirect::back()->with('message', 'Success');
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, [OrderStatus::PENDING->value, OrderStatus::PARTIALLY_PAID->value, OrderStatus::PAID->value])) {
            // if ($order->status == OrderStatus::PAID->value && now()->diffInHours($order->updated_at) >= 24) return Redirect::back()->with('message', 'Cannot cancel a paid order after 24 hours');
            DB::transaction(function () use ($order) {
                $order->update(['status' => OrderStatus::CANCELED->value]);
                $order->features->each(function ($feature) {
                    if ($feature->type == FeatureType::STOCKED->value) {
                        $feature->stock += $feature->pivot->quantity;
                        $feature->save();
                        $feature->pivot->batches->each(function ($batch) {
                            $batch->stock += $batch->pivot->quantity;
                            $batch->save();
                        });
                    }
                });

                $order->payments->each(function ($payment) {
                    Credit::create([
                        'order_payment_id' => $payment->pivot->id,
                        'amount' => $payment->pivot->amount,
                        'number' => $payment->pivot->number,
                    ]);
                });
            });
        }
        return Redirect::back()->with('message', 'Success');
    }

    public function complete(Order $order)
    {
        if (in_array($order->status, [OrderStatus::PAID->value])) {
            DB::transaction(function () use ($order) {
                $order->update(['status' => OrderStatus::COMPLETED->value]);
            });
        }
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
        $query = Item::with(['latestFeature'])->take(5);
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
            'discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'note' => ['sometimes', 'required'],
            'items' => ['required', 'array'],
            'items.*' => ['required', 'array'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.price' => ['required', 'numeric', 'gt:0'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'services' => ['sometimes', 'required', 'array'],
            'services.*' => ['required_with:services', 'array'],
            'services.*.service_id' => ['required_with:services', 'exists:services,id', 'distinct'],
            'services.*.quantity' => ['required_with:services', 'numeric', 'gt:0'],
            'services.*.discount' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ]);

        $items = Item::whereIn('id', array_map(fn ($val) => $val['item_id'], $attributes['items']))->get();
        $order = DB::transaction(function () use ($attributes, $items) {
            $amount = array_reduce($attributes['items'], fn ($carry, $val) => $carry + $val['price'] * $val['quantity'], 0);

            if (array_key_exists('services', $attributes)) {
                $services = Service::query()->whereIn('id', array_map(fn ($val) => $val['service_id'], $attributes['services']))->get(['id', 'price', 'name']);

                $amount += array_reduce($attributes['services'], fn ($carry, $val) => $carry + ($services->first(fn ($v) => $v->id == $val['service_id'])->price) * $val['quantity'], 0);
            };

            $attributes['amount'] = $amount;

            $order = Order::create(collect($attributes)->except('items', 'services')->toArray());
            foreach ($attributes['items'] as $item) {
                $order->items()->attach($item['item_id'], [
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'name' => $items->first(fn ($val) => $val->id == $item['item_id'])->id
                ]);
            }
            if (array_key_exists('services', $attributes))
                foreach ($attributes['services'] as $service) {
                    $order_service_data = [
                        'price' => $services->first(fn ($v) => $v->id == $service['service_id'])->price,
                        'quantity' => $service['quantity'],
                        'name' => $services->first(fn ($val) => $val->id == $service['service_id'])->name
                    ];
                    $order->services()->attach(
                        $service['service_id'],
                        $order_service_data
                    );
                }
            return $order;
        });

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
        if (array_key_exists('features', $attributes)) {
            $outOfStock = Feature::outOfStock($attributes['features']);
            if ($outOfStock) return Redirect::back()->with('message', $outOfStock);

            $attributes['features'] = Feature::mapForOrder($attributes['features']);

            $amount += floor((float) collect($attributes['features'])->reduce(
                fn ($carry, $feature) => $carry + ($feature['price'] - ($feature['discount'] ?? 0)) * $feature['quantity']
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

        $createdOrder = DB::transaction(function () use ($attributes, $amount, $remaining) {
            $order = Order::create(
                collect([
                    'amount' => $amount,
                    ...$attributes
                ])->except(['features', 'services'])->toArray()
            );
            if ($remaining == 0) $order->update(['status' => 3]);

            if (array_key_exists('features', $attributes)) {
                $order->features()->attach(
                    collect($attributes['features'])->mapWithKeys(fn ($feature) => [$feature['id'] => [
                        'quantity' => $feature['quantity'],
                        'price' => $feature['price'],
                        'discount' => $feature['discount'] ?? 0,
                        'name' => $feature['name']
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

            if (array_key_exists('features', $attributes)) {
                $order->features->each(function ($feature) {
                    $feature->stock -= $feature->pivot->quantity;
                    $feature->save();
                    $quantity = $feature->pivot->quantity;
                    $feature->batches()
                        ->where('stock', '>', 0)
                        ->get()
                        ->each(function ($batch) use (&$quantity, $feature) {
                            if ($quantity > 0) {
                                $feature->pivot->batches()->attach(
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        $order->load(['features', 'payments', 'items', 'services']);
        $order->payments->each(function (&$value) {
            if ($value->pivot->picture) $value->pivot->picture = Storage::url(
                config('app')['name'] .
                    '/order_payments/' .
                    config('app')['env'] .
                    '/' .
                    $value->pivot->picture
            );
        });
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
