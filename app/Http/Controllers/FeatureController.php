<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeatureRequest;
use App\Http\Requests\UpdateFeatureRequest;
use App\Models\Feature;
use App\Models\Item;
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
        $attributes = request()->validate([
            'item_id' => ['required', 'exists:items,id'],
            'search' => ['sometimes', 'required', 'string'],
        ]);
        $filters = request()->only(['search']);
        $features = Feature::with(['pictures'])->where('item_id', $attributes['item_id'])->filter($filters)->orderBy('id', 'desc')->paginate(request()->per_page ?? 20);
        return Inertia::render('Features', [
            'item' => Item::find($attributes['item_id']),
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
        Feature::create($attributes);
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
        return Inertia::render('EditFeature', ['feature' => $feature->load(['item', 'pictures']), 'edit' => request()->edit ?? 'info']);
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
