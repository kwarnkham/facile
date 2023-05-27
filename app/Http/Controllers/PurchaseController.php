<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\AItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;

class PurchaseController extends Controller
{
    public function index(): JsonResponse
    {
        $filters = request()->validate([
            'from' => ['date'],
            'to' => ['date'],
            'search' => ['sometimes'],
            'status' => ['in:' . implode(',', PurchaseStatus::all())],
            'type' => ['sometimes'],
            'group' => ['sometimes', 'numeric']
        ]);

        $query = Purchase::query()
            ->filter($filters)
            ->with(['purchasable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Product::class => ['item'],
                ]);
            }]);

        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 10),
            'total' => $query->sum(DB::connection('tenant')->raw('price * quantity'))
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

    public function group(Purchase $purchase)
    {
        $data = request()->validate([
            'group' => ['required', 'numeric']
        ]);

        $purchase->group = $data['group'];

        $purchase->save();

        return response()->json(['purchase' => $purchase]);
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
        abort_if($purchase->status == PurchaseStatus::CANCELED->value, ResponseStatus::BAD_REQUEST->value, 'Already canceled');

        if ($purchase->purchasable instanceof AItem && $purchase->purchasable->type == ProductType::STOCKED->value) {
            abort_if($purchase->purchasable->stock < $purchase->quantity, ResponseStatus::BAD_REQUEST->value, 'Cannot cancel. Order existed');
            $purchase->purchasable->update(['stock' => $purchase->purchasable->stock - $purchase->quantity]);
        }
        if ($purchase->purchasable instanceof Order && $purchase->purchasable->status == OrderStatus::COMPLETED->value) {
            abort(ResponseStatus::BAD_REQUEST->value, 'Order is already completed');
        }
        $purchase->status = 2;
        $purchase->save();


        return response()->json(['purchase' => $purchase->fresh(['purchasable' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                Product::class => ['item'],
            ]);
        }])]);
    }
}
