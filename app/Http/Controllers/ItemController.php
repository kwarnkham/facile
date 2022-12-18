<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Feature;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $validator = Validator::make(request()->only(['search']), [
            'search' => ['string']
        ]);

        $query = Item::query();
        $filters = $validator->safe()->only(['search']);
        return Inertia::render('Items', [
            'items' => $query->filter($filters)->with(['pictures'])->paginate(request()->per_page ?? 20),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('CreateItem');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreItemRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreItemRequest $request)
    {
        $attributes = $request->validated();
        $item = Item::create($attributes);
        return Redirect::route('items.edit', ['item' => $item->id])->with('message', 'success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        $item->features = Feature::whereBelongsTo($item)->with(['pictures'])->paginate(20);
        return Inertia::render('Item', ['item' => $item->load(['pictures', 'tags', 'wholesales'])]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit(Item $item)
    {
        $tags = Tag::all();
        return Inertia::render('EditItem', [
            'item' => $item->load(['pictures', 'tags', 'wholesales']),
            'tags' => $tags,
            'edit' => request()->edit ?? 'info',
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateItemRequest  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());
        return Redirect::route('items.edit', ['item' => $item->id])->with('message', 'success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy(Item $item)
    {
        //
    }
}
