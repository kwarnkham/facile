<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Credit;
use App\Models\Feature;
use App\Models\Order;
use App\Models\MerchantPayment;
use App\Models\Picture;
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
            'note' => ['sometimes', 'required', 'string'],
            'picture' => ['sometimes', 'required', 'image']
        ]);

        DB::beginTransaction();
        try {
            $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'payments') : null;
            $order->merchantPayments()->attach(
                $attributes['payment_id'],
                [
                    'amount' => $attributes['amount'],
                    'number' => MerchantPayment::find($attributes['payment_id'])->number,
                    'note' => $attributes['note'] ?? null,
                    'picture' => $picture
                ]
            );
            if ((strval($attributes['amount']) + strval($paidAmount)) < strval($order->amount)) $order->status = 2;
            else if ((strval($attributes['amount']) + strval($paidAmount)) == strval($order->amount)) $order->status = 3;
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
            if ($order->status == OrderStatus::PAID->value && now()->diffInHours($order->updated_at) >= 24) return Redirect::back()->with('message', 'Cannot cancel a completed order after 24 hours');
            DB::transaction(function () use ($order) {
                $order->update(['status' => OrderStatus::CANCELED->value]);
                $order->features->each(function ($feature) {
                    if ($feature->type != 2) {
                        $feature->stock += $feature->pivot->quantity;
                        $feature->save();
                    }
                });

                $order->merchantPayments->each(function ($merchantPayment) {
                    Credit::create([
                        'order_payment_id' => $merchantPayment->pivot->id,
                        'amount' => $merchantPayment->pivot->amount,
                        'number' => $merchantPayment->pivot->number,
                    ]);
                });
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
        $features = Feature::whereIn('id', array_map(fn ($v) => $v['id'], $attributes['features']))->with(['discounts', 'item.wholesales'])->get();
        $attributes['features'] = collect($attributes['features'])->map(function ($feature) use ($features) {
            $features->each(function ($val) use (&$feature) {
                if ($val->id == $feature['id']) {
                    $wholesalePrice = $val->item->wholesales->first(fn ($v) => $v->quantity <= $feature['quantity'])->price ?? null;
                    $feature['price'] = $wholesalePrice ?? $val->price;
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
        $order->load(['features', 'merchantPayments.payment']);
        $order->merchantPayments->each(function (&$value) {
            if ($value->pivot->picture) $value->pivot->picture = $value->picture;
        });
        return Inertia::render('Order', ['order' => $order, 'merchant_payments' => MerchantPayment::with(['payment'])->where('merchant_id', $order->merchant->id)->get()]);
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
