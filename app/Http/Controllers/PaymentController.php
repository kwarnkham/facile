<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\ResponseStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Models\Picture;
use Redirect;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            ['data' => Payment::with('paymentType')->orderBy('id', 'desc')->paginate(request()->per_page ?? 20)]
        );
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
     * @param  \App\Http\Requests\StorePaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePaymentRequest $request)
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['payment'] >= 0 &&
                $tenant->plan_usage['payment'] <= Payment::query()->count(),
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan_usage['payment'] . ' payments.'
        );
        $attributes = $request->validated();
        $qr = array_key_exists('qr', $attributes) ? Picture::savePictureInDisk($attributes['qr'], 'payments') : null;
        if ($qr) {
            $attributes['qr'] = $qr;
        }
        $payment = Payment::create($attributes);
        if ($request->wantsJson())
            return response()->json(['payment' => $payment->fresh()->load(['paymentType'])]);
    }

    public function toggle(Payment $payment)
    {
        $payment->status = $payment->status == PaymentStatus::ENABLED->value ? PaymentStatus::DISABLED->value : PaymentStatus::ENABLED->value;
        $payment->save();
        if (request()->wantsJson()) return response()->json(['payment' => $payment->load(['paymentType'])]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaymentRequest  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $attributes = $request->validated();
        if (array_key_exists('qr', $attributes)) {
            if ($payment->qr)
                abort_unless(Picture::deletePictureFromDisk($payment->getRawOriginal('qr'), 'payments'), ResponseStatus::SERVER_ERROR->value, 'Failed to delete existed QR');

            $qr =  Picture::savePictureInDisk($attributes['qr'], 'payments');
            $attributes['qr'] = $qr;
        }

        $payment->update($attributes);

        if ($request->wantsJson()) return response()->json(['payment' => $payment->load(['paymentType'])]);
        return Redirect::back()->with('message', 'Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
