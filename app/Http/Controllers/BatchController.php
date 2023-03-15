<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class BatchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function correct(Batch $batch)
    {
        $attributes = request()->validate([
            'stock' => ['required', 'numeric'],
            'type' => ['required', 'in:1,2']
        ]);
        $product = DB::transaction(function () use ($batch, $attributes) {
            $batch->corrections()->create($attributes);
            $product = $batch->product;
            if ($attributes['type'] == 1) {
                if ($attributes['stock'] > $batch->stock) abort(ResponseStatus::BAD_REQUEST->value, 'No enough stock to reduce');
                $batch->stock -= $attributes['stock'];
                $product->stock -= $attributes['stock'];
            } else if ($attributes['type'] == 2) {
                $batch->stock += $attributes['stock'];
                $product->stock += $attributes['stock'];
            }
            $batch->save();
            $product->save();
            return $product;
        });
        $product->load(['item', 'pictures', 'batches', 'latestPurchase']);
        if (request()->wantsJson()) return response()->json(['product' => $product]);

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
     * @param  \App\Http\Requests\StoreBatchRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBatchRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\Response
     */
    public function show(Batch $batch)
    {
        return Inertia::render('Batch', ['batch' => $batch->load(['product'])]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\Response
     */
    public function edit(Batch $batch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBatchRequest  $request
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Batch  $batch
     * @return \Illuminate\Http\Response
     */
    public function destroy(Batch $batch)
    {
        //
    }
}
