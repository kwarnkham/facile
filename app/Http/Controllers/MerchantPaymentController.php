<?php

namespace App\Http\Controllers;

use App\Models\MerchantPayment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MerchantPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $merchantPayments = MerchantPayment::with(['payment'])->where('merchant_id', request()->user()->merchant->id)->get();
        return Inertia::render('MerchantPayments', ['merchant_payments' => $merchantPayments, 'payments' => Payment::all()]);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'payment_id' => ['required', 'exists:payments,id'],
            'number' => [
                'required',
                'numeric',
                Rule::unique('merchant_payments', 'number')->where(fn ($query) => $query->where([
                    'payment_id' => $request->payment_id,
                    'merchant_id' => $request->user()->merchant->id
                ]))
            ],
        ]);

        $attributes['merchant_id'] = $request->user()->merchant->id;
        MerchantPayment::create($attributes);

        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(MerchantPayment $merchantPayment)
    {
        // $merchantPayment->delete();
        // return Redirect::back()->with('message', 'Deleted');
    }

    public function disable(MerchantPayment $merchantPayment)
    {
        $merchantPayment->status = 2;
        $merchantPayment->save();
        return Redirect::back()->with('message', 'Success');
    }
}
