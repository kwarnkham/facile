<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Item;
use App\Models\Picture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->wantsJson()) {
            $filters = request()->validate([
                'search' => ['sometimes', 'required'],
                'stocked' => ['boolean'],
                'item' => ['sometimes', 'numeric'],
                'limit' => ['sometimes', 'numeric']
            ]);
            $query = Product::query()->with(['item', 'latestBatch.purchase'])
                ->filter($filters)
                ->orderBy('id', 'desc');
            if (request()->exists('limit')) $products = $query->get();
            else $products = $query->paginate(request()->per_page ?? 20);
            return response()->json([
                'data' => $products
            ]);
        }
        $attributes = request()->validate([
            'item_id' => ['required', 'exists:items,id'],
            'search' => ['sometimes', 'required'],
            'stocked' => ['boolean']
        ]);
        $filters = request()->only(['search', 'stocked']);
        $products = Product::with(['pictures'])->where('item_id', $attributes['item_id'])->filter($filters)->orderBy('id', 'desc')->paginate(request()->per_page ?? 20);
        return Inertia::render('Product', [
            'item' => Item::find($attributes['item_id']),
            'products' => $products,
            'filters' => $filters
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        $attributes = $request->validated();
        $product = DB::transaction(function () use ($attributes) {
            $product = Product::create(collect($attributes)->except('purchase_price', 'expired_on', 'picture')->toArray());

            $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'purchases') : null;

            $data = [
                'price' => $attributes['purchase_price'],
                'quantity' => $attributes['stock'],
                'name' => $product->name,
            ];

            if ($picture) {
                $data['picture'] = $picture;
            }
            $purchase = $product->purchases()->create($data);
            $product->batches()->create([
                'purchase_id' => $purchase->id,
                'expired_on' => $attributes['expired_on'] ?? null,
                'stock' => $attributes['stock']
            ]);
            return $product;
        });

        if ($request->wantsJson()) return response()->json(['product' => $product]);
        return Redirect::route('products.index', ['item_id' => $attributes['item_id']]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $product->load(['item', 'pictures', 'batches', 'latestPurchase']);
        if (request()->wantsJson()) return response()->json([
            'product' => $product
        ]);
        return Inertia::render('Product', ['product' => $product]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $attributes = $request->validated();
        $product->update($attributes);
        if ($request->wantsJson()) return response()->json(['product' => $product->load([
            'item',
            'latestBatch.purchase'
        ])]);
        return Redirect::route('products.edit', ['product' => $product->id])->with('message', 'Success');
    }

    public function restock(Product $product)
    {
        $attributes = request()->validate([
            'price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'expired_on' => ['sometimes', 'required', 'date'],
            'picture' => ['sometimes', 'required', 'image']
        ]);
        $result = DB::transaction(function () use ($product, $attributes) {
            $data = collect($attributes)->except('expired_on')->toArray();
            $data['name'] = $product->name;

            $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'purchases') : null;
            if ($picture) {
                $data['picture'] = $picture;
            }

            $purchase = $product->purchases()->create($data);
            $product->stock += $attributes['quantity'];
            $product->save();
            $product->batches()->create([
                'purchase_id' => $purchase->id,
                'expired_on' => $attributes['expired_on'] ?? null,
                'stock' => $attributes['quantity']
            ]);
            return $product;
        });
        if (request()->wantsJson()) return response()->json(['product' => $result->load(['latestBatch.purchase', 'item'])]);

        return Redirect::back()->with('message', $result ? 'Success' : 'Failed');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
