<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeatureRequest;
use App\Http\Requests\UpdateFeatureRequest;
use App\Models\Feature;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->wantsJson()) {
            request()->validate([
                'search' => ['sometimes', 'required'],
                'stocked' => ['boolean'],
            ]);

            $filters = request()->only(['search', 'stocked']);
            $features = Feature::with(['item', 'latestBatch.purchase'])->filter($filters)->latest()->paginate(request()->per_page ?? 20);
            return response()->json($features);
        }
        $attributes = request()->validate([
            'item_id' => ['required', 'exists:items,id'],
            'search' => ['sometimes', 'required'],
            'stocked' => ['boolean']
        ]);
        $filters = request()->only(['search', 'stocked']);
        $features = Feature::with(['pictures'])->where('item_id', $attributes['item_id'])->filter($filters)->orderBy('id', 'desc')->paginate(request()->per_page ?? 20);
        return Inertia::render('Features', [
            'item' => Item::find($attributes['item_id']),
            'features' => $features,
            'filters' => $filters
        ]);
    }

    public function all()
    {
        request()->validate([
            'search' => ['sometimes', 'required'],
            'stocked' => ['boolean'],
        ]);

        $filters = request()->only(['search', 'stocked']);
        $features = Feature::with(['pictures'])->filter($filters)->orderBy('id', 'desc')->paginate(request()->per_page ?? 20);
        return Inertia::render('AllFeatures', [
            'features' => $features,
            'filters' => $filters
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        request()->validate([
            'item_id' => ['required', 'exists:items,id']
        ]);
        return Inertia::render('CreateFeature', ['item' => Item::find(request()->item_id)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreFeatureRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFeatureRequest $request)
    {
        $attributes = $request->validated();
        $feature = DB::transaction(function () use ($attributes) {
            $feature = Feature::create(collect($attributes)->except('purchase_price', 'expired_on')->toArray());
            $purchase = $feature->purchases()->create([
                'price' => $attributes['purchase_price'],
                'quantity' => $attributes['stock'],
                'name' => $feature->name
            ]);
            $feature->batches()->create([
                'purchase_id' => $purchase->id,
                'expired_on' => $attributes['expired_on'] ?? null,
                'stock' => $attributes['stock']
            ]);
            return $feature;
        });

        if ($request->wantsJson()) return response()->json(['feature' => $feature]);
        return Redirect::route('features.index', ['item_id' => $attributes['item_id']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Feature  $feature
     * @return \Illuminate\Http\Response
     */
    public function show(Feature $feature)
    {
        return Inertia::render('Feature', ['feature' => $feature->load(['item', 'pictures'])]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Feature  $feature
     * @return \Illuminate\Http\Response
     */
    public function edit(Feature $feature)
    {
        return Inertia::render('EditFeature', ['feature' => $feature->load(['item', 'pictures', 'batches']), 'edit' => request()->edit ?? 'info']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFeatureRequest  $request
     * @param  \App\Models\Feature  $feature
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFeatureRequest $request, Feature $feature)
    {
        $attributes = $request->validated();
        $feature->update($attributes);
        return Redirect::route('features.edit', ['feature' => $feature->id])->with('message', 'Success');
    }

    public function restock(Feature $feature)
    {
        $attributes = request()->validate([
            'price' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'expired_on' => ['sometimes', 'required', 'date']
        ]);
        $result = DB::transaction(function () use ($feature, $attributes) {
            $data = collect($attributes)->except('expired_on')->toArray();
            $data['name'] = $feature->name;
            $purchase = $feature->purchases()->create($data);
            $feature->stock += $attributes['quantity'];
            $feature->save();
            $feature->batches()->create([
                'purchase_id' => $purchase->id,
                'expired_on' => $attributes['expired_on'] ?? null,
                'stock' => $attributes['quantity']
            ]);
            return $feature;
        });
        if (request()->wantsJson()) return response()->json(['feature' => $result->load(['latestBatch.purchase', 'item'])]);

        return Redirect::back()->with('message', $result ? 'Success' : 'Failed');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Feature  $feature
     * @return \Illuminate\Http\Response
     */
    public function destroy(Feature $feature)
    {
        //
    }
}
