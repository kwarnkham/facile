<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        $validator = Validator::make(request()->only(['user_id', 'search']), [
            'user_id' => ['required', 'numeric'],
            'search' => ['string']
        ]);

        if (in_array('user_id', $validator->errors()->keys())) return redirect()->route(
            'items.index',
            ['user_id' => User::whereHas('roles', function (Builder $query) {
                $query->where('name', 'merchant');
            })->first()->id]
        );

        $query = Item::query();
        $filters = $validator->safe()->only(['user_id', 'search']);
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
        $item = Item::create([...$attributes, 'user_id' => $request->user()->id]);
        return Redirect::route('items.show', ['item' => $item->id])->with('message', 'success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        return Inertia::render('Item', ['item' => $item->load(['pictures', 'tags'])]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function edit(Item $item)
    {
        $tags = Tag::whereRelation('items', 'user_id', '=', $item->user->id)->get();
        return Inertia::render('EditItem', ['item' => $item->load(['pictures', 'tags', 'features']), 'tags' => $tags]);
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
        return Redirect::route('items.show', ['item' => $item->id])->with('message', 'success');
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
