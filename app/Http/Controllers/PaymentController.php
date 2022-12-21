<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\ResponseStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Models\Picture;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
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
        return Inertia::render('Payments', [
            'payment_types' => DB::table('payment_types')->get(),
            'payments' => Payment::all()
        ]);
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
        $attributes = $request->validated();
        $qr = array_key_exists('qr', $attributes) ? Picture::savePictureInDisk($attributes['qr'], 'payments') : null;
        if ($qr) {
            $attributes['qr'] = $qr;
        }
        Payment::create($attributes);
    }

    public function toggle(Payment $payment)
    {
        $payment->status = $payment->status == PaymentStatus::ENABLED->value ? PaymentStatus::DISABLED->value : PaymentStatus::ENABLED->value;
        $payment->save();
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
        return Inertia::render('EditPayment', ['payment' => $payment]);
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
