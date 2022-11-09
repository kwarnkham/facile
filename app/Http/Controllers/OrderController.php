<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Feature;
use App\Models\Order;
use App\Models\MerchantPayment;
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
        $order = Order::paginate(request()->per_page ?? 20);
        return Inertia::render('Orders', ['orders' => $order]);
    }

    /**
     * Pay the order
     *
     * @return \Inertia\Response
     */
    public function pay(Order $order)
    {
        $attributes = request()->validate([
            'payment_id' => ['required', Rule::exists('merchant_payments', 'id')->where('merchant_id', request()->user()->merchant->id)],
            'amount' => ['required', 'numeric', 'gt:0']
        ]);

        $order->payments()->attach(
            $attributes['payment_id'],
            [
                'amount' => $attributes['amount'],
                'number' => MerchantPayment::find($attributes['payment_id'])->number
            ]
        );

        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        $prices = collect(Feature::whereIn('id', array_map(fn ($v) => $v['id'], $attributes['features']))->get(['id', 'price'])->toArray());
        $attributes['features'] = collect($attributes['features'])->map(function ($feature) use ($prices) {
            $feature['price'] = $prices->first(fn ($price) => $price['id'] == $feature['id'])['price'];
            return $feature;
        });

        $createdOrder = DB::transaction(function () use ($attributes, $request, $prices) {
            $order = Order::create(
                collect([
                    ...[
                        'user_id' => $request->user()->id,
                        'amount' => collect($attributes['features'])->reduce(fn ($carry, $feature) => $carry + $feature['price']),
                    ],
                    ...$attributes
                ])->except(['features'])->toArray()
            );
            $order->features()->attach(
                collect($attributes['features'])->mapWithKeys(fn ($feature) => [$feature['id'] => [
                    'quantity' => $feature['quantity'],
                    'price' => $feature['price']
                ]])->toArray()
            );
            return $order;
        });

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
        return Inertia::render('Order', ['order' => $order->load(['features'])]);
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
