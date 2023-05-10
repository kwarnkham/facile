<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\ResponseStatus;
use App\Models\AItem;
use Illuminate\Database\Query\Builder;
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
            ->orderBy('stock', 'asc');

        if (request()->exists('limit')) $aItems = $query->get();
        else $aItems = $query->paginate(request()->per_page ?? 20);

        return response()->json([
            'data' => $aItems
        ]);
    }

    public function store()
    {
        $attributes = request()->validate([
            'name' => ['required', Rule::unique('a_items', 'name')->where('type', request()->type)],
            'stock' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required'],
            'purchase_price' => ['required', 'numeric'],
            'type' => ['required', 'in:1,2'],
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
            if ($aItem->type == ProductType::STOCKED->value)
                $aItem->recordPurchase($attributes);

            return $aItem;
        });
        return response()->json(['a_item' => $aItem->fresh(['latestPurchase'])]);
    }

    public function show(AItem $aItem)
    {
        $aItem->load(['purchases', 'latestPurchase']);
        $aItem->ordered_quantity = DB::table('a_item_order')
            ->where([
                ['a_item_id', '=', $aItem->id,]
            ])
            ->sum('quantity');

        return response()->json([
            'a_item' => $aItem
        ]);
    }

    public function update(AItem $aItem)
    {
        $attributes = request()->validate([
            'name' => [
                'required', Rule::unique('a_items', 'name')
                    ->where(
                        fn (Builder $query) =>
                        $query->where('type', $aItem->type)
                    )->ignoreModel($aItem)
            ],
            'price' => ['required', 'numeric'],
            'note' => ['']
        ]);
        abort_if($aItem->orders()->whereIn('orders.status', [
            OrderStatus::PENDING->value,
            OrderStatus::PARTIALLY_PAID->value,
            OrderStatus::PAID->value,
            OrderStatus::PACKED->value,
            OrderStatus::ON_DELIVERY->value,
        ])->count() > 0, ResponseStatus::BAD_REQUEST->value, 'Please finish orders to update item info');
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
