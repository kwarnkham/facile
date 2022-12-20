<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Batch;
use App\Models\Credit;
use App\Models\Feature;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Picture;
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
            if ($order->status == OrderStatus::PAID->value && now()->diffInHours($order->updated_at) >= 24) return Redirect::back()->with('message', 'Cannot cancel a paid order after 24 hours');
            DB::transaction(function () use ($order) {
                $order->update(['status' => OrderStatus::CANCELED->value]);
                $order->features->each(function ($feature) {
                    if ($feature->type != 2) {
                        $feature->stock += $feature->pivot->quantity;
                        $feature->save();

                        $batch = Batch::find($feature->pivot->batch_id);
                        $batch->stock += $feature->pivot->quantity;
                        $batch->save();
                    }
                });

                $order->payments->each(function ($payment) {
                    Credit::create([
                        'order_payment_id' => $payment->id,
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
        $features = Feature::whereIn('id', array_map(fn ($v) => $v['id'], $attributes['features']))->with(['item.wholesales'])->get();
        $attributes['features'] = collect($attributes['features'])->map(function ($feature) use ($features) {
            $features->each(function ($val) use (&$feature) {
                if ($val->id == $feature['id']) {
                    $feature['price'] = $val->price;
                    $feature['batch'] = $val->batches()->where('stock', '>', 0)->first();
                    $feature['batch_id'] = $feature['batch']->id;
                }
            });
            return $feature;
        });
        $amount = floor((float) collect($attributes['features'])->reduce(
            fn ($carry, $feature) => $carry + ($feature['price'] - ($feature['discount'] ?? 0)) * $feature['quantity']
        ));
        $remaining = $amount -
            floor($attributes['discount'] ?? 0);
        if ($remaining < 0) return Redirect::back()->with('message', 'Total discount is greater than the amount');

        $createdOrder = DB::transaction(function () use ($attributes, $amount, $remaining) {
            $order = Order::create(
                collect([
                    'amount' => $amount,
                    ...$attributes
                ])->except(['features'])->toArray()
            );
            if ($remaining == 0) $order->update(['status' => 3]);
            $order->features()->attach(
                collect($attributes['features'])->mapWithKeys(fn ($feature) => [$feature['id'] => [
                    'quantity' => $feature['quantity'],
                    'price' => $feature['price'],
                    'discount' => $feature['discount'] ?? 0,
                    'batch_id' => $feature['batch_id']
                ]])->toArray()
            );

            foreach ($attributes['features'] as $val) {
                $feature = Feature::find($val['id']);
                $feature->stock -= $val['quantity'];
                $feature->save();

                $batch = $val['batch'];
                $batch->stock -= $val['quantity'];
                $batch->save();
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
        $order->load(['features', 'payments']);
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
            'payment_types' => DB::table('payment_types')->get()
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
