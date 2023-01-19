<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Batch;
use App\Models\Feature;
use App\Models\FeatureOrder;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filters = request()->validate([
            'from' => ['date'],
            'to' => ['date'],
            'search' => ['sometimes'],
            'status' => ['in:' . implode(',', PurchaseStatus::all())]
        ]);

        $query = Purchase::query()
            ->filter($filters);
        $total = $query->get(['price', 'quantity'])->reduce(fn ($carry, $value) => $carry + $value->price * $value->quantity, 0);
        $data = $query->with(['purchasable' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                Feature::class => ['item'],
            ]);
        }])
            ->paginate(request()->per_page ?? 10);

        if (request()->wantsJson()) return response()->json([
            'data' => $data,
            'total' => $total
        ]);
        return Inertia::render('Purchases', [
            'purchases' => $data,
            'filters' => [
                'from' => $filters['from'],
                'to' => $filters['to']->startOfDay(),
            ],
            'total' => $total
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
     * @param  \App\Http\Requests\StorePurchaseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseRequest $request)
    {
        $attributes = $request->validated();
        $model = 'App\\Models\\' . ucfirst(strtolower($attributes['type']));

        $attributes['purchasable_id'] = $attributes['type_id'];
        $attributes['purchasable_type'] = $model;

        Purchase::create(collect($attributes)->except(['type_id', 'type'])->toArray());

        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePurchaseRequest  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        //
    }

    public function cancel(Purchase $purchase)
    {
        if ($purchase->status == 2) return Redirect::back()->with('message', 'Already canceled');

        if ($purchase->purchasable instanceof Feature) {
            $batch = Batch::where('purchase_id', $purchase->id)->first();
            $featureOrderId = DB::table('batch_feature_order')->where('batch_id', $batch->id)->first('feature_order_id');
            if (
                !is_null($featureOrderId)
                && DB::table('orders')
                ->where('id', FeatureOrder::find($featureOrderId->feature_order_id)->order_id)
                ->first('status')->status != OrderStatus::CANCELED->value
            )
                return Redirect::back()->with('message', 'Non canceled order associated with this purchase exists');

            else {
                $purchase->status = 2;
                $purchase->save();

                $feature = $purchase->purchasable;
                $feature->stock -= $purchase->quantity;
                $feature->save();


                $batch->stock -= $purchase->quantity;
                $batch->save();
            }
        } else {
            $purchase->status = 2;
            $purchase->save();
        }

        if (request()->wantsJson()) return response()->json(['message' => 'Success']);
        return Redirect::back()->with('message', 'success');
    }
}
