<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Support\Facades\Redirect;
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
        $filters = request()->validate([
            'search' => ['sometimes', 'required'],
            'limit' => ['sometimes', 'numeric']
        ]);
        $query = Item::query()
            ->with(['latestProduct.latestPurchase'])
            ->filter($filters)
            ->orderBy('id', 'desc');

        if (request()->wantsJson()) {
            if (request()->exists('limit')) $items = $query->get();
            else $items = $query->paginate(request()->per_page ?? 20);
            return response()->json([
                'data' => $items
            ]);
        }
        $data = [
            'items' => $query->paginate(request()->per_page ?? 20),
            'filters' => $filters,
        ];

        return Inertia::render('Items', $data);
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
        return response()->json(['item' => $item]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function show(Item $item)
    {
        $filters = request()->validate([
            'search' => ['sometimes']
        ]);
        $item->qr = $item->generateQR()->toHtml();
        if (request()->wantsJson()) return response()->json(['item' => $item]);
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
        return response()->json([
            'item' => $item
        ]);
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
