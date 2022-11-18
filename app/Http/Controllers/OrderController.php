<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Feature;
use App\Models\Order;
use App\Models\MerchantPayment;
use App\Models\Payment;
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
        if ($order->status == 3) return Redirect::back()->with('message', 'Order cannot be paid anymore');
        $paidAmount = $order->paidAmount() + $order->discount + $order->getFeatureDiscounts();
        $remaining = floor($order->amount - $paidAmount);
        $attributes = request()->validate([
            'payment_id' => ['required', Rule::exists('merchant_payments', 'id')->where('merchant_id', request()->user()->merchant->id)],
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
            'note' => ['sometimes', 'required', 'string']
        ]);

        DB::transaction(function () use ($order, $attributes, $paidAmount) {
            $order->payments()->attach(
                $attributes['payment_id'],
                [
                    'amount' => $attributes['amount'],
                    'number' => MerchantPayment::find($attributes['payment_id'])->number,
                    'note' => $attributes['note'] ?? null
                ]
            );
            if ((strval($attributes['amount']) + strval($paidAmount)) < strval($order->amount)) $order->status = 2;
            else if ((strval($attributes['amount']) + strval($paidAmount)) == strval($order->amount)) $order->status = 3;
            $order->save();
        });


        return Redirect::back()->with('message', 'Success');
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, [OrderStatus::PENDING->value])) {
            $order->update(['status' => OrderStatus::CANCELED->value]);
            $order->features->each(function ($feature) {
                $feature->stock += $feature->pivot->quantity;
                $feature->save();
            });
        }
        return Redirect::back();
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
        foreach ($attributes['features'] as $val) {
            $feature = Feature::find($val['id']);
            if ($feature->stock < $val['quantity']) return Redirect::back()->with('message', $feature->name . ' is out of stock');
        }
        $features = Feature::whereIn('id', array_map(fn ($v) => $v['id'], $attributes['features']))->with(['discounts'])->get();
        $attributes['features'] = collect($attributes['features'])->map(function ($feature) use ($features) {
            $features->each(function ($val) use (&$feature) {
                if ($val->id == $feature['id']) {
                    $feature['price'] = $val->price;
                    $feature['discount'] = $val->totalDiscount();
                }
            });
            return $feature;
        });
        $amount = floor((float) collect($attributes['features'])->reduce(fn ($carry, $feature) => $carry + $feature['price'] * $feature['quantity']));

        $featuresDiscount = floor((float)collect($attributes['features'])->reduce(fn ($carry, $feature) => $carry + $feature['discount'] * $feature['quantity'], 0));

        $remaining = $amount -
            floor($attributes['discount'] ?? 0) - $featuresDiscount;

        if ($remaining < 0) return Redirect::back()->with('message', 'Total discount is greater than the amount');

        $createdOrder = DB::transaction(function () use ($attributes, $request, $amount, $remaining) {
            $order = Order::create(
                collect([
                    ...[
                        'merchant_id' => $request->user()->merchant->id,
                        'amount' => $amount,
                    ],
                    ...$attributes
                ])->except(['features'])->toArray()
            );

            if ($remaining == 0) $order->update(['status' => 3]);
            $order->features()->attach(
                collect($attributes['features'])->mapWithKeys(fn ($feature) => [$feature['id'] => [
                    'quantity' => $feature['quantity'],
                    'price' => $feature['price'],
                    'discount' => $feature['discount']
                ]])->toArray()
            );

            foreach ($attributes['features'] as $val) {
                $feature = Feature::find($val['id']);
                $feature->stock -= $val['quantity'];
                $feature->save();
            }
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
        return Inertia::render('Order', ['order' => $order->load(['features', 'payments']), 'merchant_payments' => MerchantPayment::with(['payment'])->where('merchant_id', $order->merchant->id)->get()]);
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
