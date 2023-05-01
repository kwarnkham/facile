<?php

namespace App\Http\Controllers;

use App\Models\AItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AItemController extends Controller
{
    public function index()
    {
        $filters = request()->validate([
            'search' => [''],
            'minStock' => ['numeric'],
            'limit' => ['numeric']
        ]);

        $query = AItem::query()
            ->filter($filters)
            ->with(['latestPurchase'])
            ->orderBy('id', 'desc');

        if (request()->exists('limit')) $aItems = $query->get();
        else $aItems = $query->paginate(request()->per_page ?? 20);

        return response()->json([
            'data' => $aItems
        ]);
    }

    public function store()
    {
        $attributes = request()->validate([
            'name' => ['required', 'unique:a_items,name'],
            'stock' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required'],
            'purchase_price' => ['required', 'numeric'],
            'type' => ['sometimes', 'required', 'in:1,2'],
            'expired_on' => ['sometimes', 'required', 'date'],
            'picture' => ['sometimes', 'required', 'image']
        ]);
        $aItem = DB::transaction(function () use ($attributes) {
            $aItem = AItem::create(
                collect($attributes)->except(
                    'purchase_price',
                    'expired_on',
                    'picture'
                )->toArray()
            );

            $aItem->recordPurchase($attributes);

            return $aItem;
        });
        return response()->json(['a_item' => $aItem]);
    }

    public function show(AItem $aItem)
    {
        $aItem->load(['pictures', 'latestPurchase']);

        return response()->json([
            'a_item' => $aItem
        ]);
    }

    public function update(AItem $aItem)
    {
        $attributes = request()->validate([
            'name' => ['required', Rule::unique('a_items', 'name')->ignore($aItem->id)],
            'price' => ['required', 'numeric'],
            'note' => ['']
        ]);
        $aItem->update($attributes);
        return response()->json(['a_item' => $aItem->load([
            'latestPurchase'
        ])]);
    }

    public function restock(Aitem $aItem)
    {
        $attributes = request()->validate([
            'price' => ['required', 'numeric'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);
        $result = DB::transaction(function () use ($aItem, $attributes) {
            $attributes['name'] = $aItem->name;
            $aItem->purchases()->create($attributes);
            $aItem->update(['stock' => $aItem->stock + $attributes['quantity']]);
            return $aItem;
        });
        return response()->json(['a_item' => $result->load(['latestPurchase'])]);
    }
}
