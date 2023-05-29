<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use App\Enums\ResponseStatus;
use App\Models\AItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;



class OrderController extends Controller
{
    public function index()
    {

        $filters = request()->validate([
            'status' => ['sometimes', 'required'],
            'from' => ['date'],
            'to' => ['date'],
            'search' => ['sometimes', 'required'],
        ]);
        $query =  Order::query()->filter($filters);

        $orders = $query
            ->with(['purchases'])
            ->orderBy('id', 'desc')
            ->paginate(request()->per_page ?? 20);


        return response()->json([
            'data' => $orders,
            'total' => $query->sum('paid')
        ]);
    }

    public function purchase(Order $order)
    {
        abort_if(
            in_array($order->value, [OrderStatus::COMPLETED->value, OrderStatus::CANCELED->value]),
            ResponseStatus::BAD_REQUEST->value,
            'Cannot purchase a canceled or completed order'
        );
        $attributes = request()->validate([
            'price' => ['required', 'gt:0', 'numeric'],
            'quantity' => ['required', 'gt:0', 'numeric'],
            'name' => ['required']
        ]);

        $order->purchases()->create($attributes);
        return response()->json([
            'order' => $order->load(['purchases', 'aItems'])
        ]);
    }


    public function status()
    {
        return response()->json(['statuses' => OrderStatus::array()]);
    }


    public function record(Order $order = null)
    {
        $attributes = request()->validate([
            'customer' => [''],
            'phone' => [''],
            'address' => ['max:255'],
            'note' => ['max:255'],
            'created_at' => ['sometimes', 'date'],
            'discount' => ['sometimes', 'numeric'],
            'paid' => ['sometimes', 'numeric'],
            'a_items' => ['required', 'array'],
            'a_items.*' => ['required', 'array'],
            'a_items.*.id' => ['required', 'exists:tenant.a_items,id'],
            'a_items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'a_items.*.discount' => ['sometimes', 'required', 'numeric'],
            'status' => [Rule::in([
                OrderStatus::PACKED->value,
                OrderStatus::CANCELED->value,
                OrderStatus::COMPLETED->value,
                OrderStatus::ON_DELIVERY->value,
            ])]
        ]);

        abort_if(
            ($attributes['discount'] ?? 0) > 0 && !request()->user()->hasRole('admin'),
            ResponseStatus::UNAUTHORIZED->value,
            'You cannot give order discount'
        );

        abort_if(
            count(array_filter($attributes['a_items'], function ($aItem) {
                return ($aItem['discount'] ?? 0) > 0;
            })) && !request()->user()->hasRole('admin'),
            ResponseStatus::UNAUTHORIZED->value,
            'You cannot give product discount'
        );

        abort_if(
            $order != null &&
                in_array($order->status, [
                    OrderStatus::CANCELED->value,
                    OrderStatus::COMPLETED->value,
                ]),
            ResponseStatus::BAD_REQUEST->value,
            'Order is already completed or canceled'
        );

        if (array_key_exists('status', $attributes)) {
            abort_if(
                $order == null,
                ResponseStatus::BAD_REQUEST->value,
                'Order does not exist'
            );

            abort_if(
                $attributes['status'] == OrderStatus::PACKED->value &&
                    $order->status != OrderStatus::PAID->value,
                ResponseStatus::BAD_REQUEST->value,
                'Can only pack a paid order'
            );

            abort_if(
                $attributes['status'] == OrderStatus::ON_DELIVERY->value &&
                    $order->status != OrderStatus::PACKED->value,
                ResponseStatus::BAD_REQUEST->value,
                'Can only deliver a packed order'
            );

            abort_if(
                $attributes['status'] == OrderStatus::COMPLETED->value &&
                    in_array($order->status, [
                        OrderStatus::CANCELED->value,
                        OrderStatus::PARTIALLY_PAID->value,
                        OrderStatus::PENDING->value
                    ]),
                ResponseStatus::BAD_REQUEST->value,
                'Cannot complete the order yet'
            );

            abort_if(
                $attributes['status'] == OrderStatus::CANCELED->value &&
                    $order->status == OrderStatus::COMPLETED->value,
                ResponseStatus::BAD_REQUEST->value,
                'Cannot cancel a completed order'
            );

            $order->update(['status' => $attributes['status']]);
            if ($attributes['status'] == OrderStatus::CANCELED->value) {
                $order->update([
                    'paid' => 0
                ]);
                $order->purchases()->update(['status' => PurchaseStatus::CANCELED->value]);
                $order->reverseStock();
            }
            return response()->json([
                'order' => $order->load(['aItems', 'purchases'])
            ]);
        }

        $data = DB::connection('tenant')->transaction(function () use ($attributes, $order) {
            if ($order != null) $order->reverseStock();

            AItem::checkStock($attributes['a_items']);

            $attributes['a_items'] = AItem::mapForOrder($attributes['a_items']);

            $amount =  collect($attributes['a_items'])->reduce(
                fn ($carry, $product) => $carry + ($product['price'] - ($product['discount'] ?? 0)) * $product['quantity']
            );

            $remaining = $amount - ($attributes['discount'] ?? 0) - ($attributes['paid'] ?? 0);

            abort_if($remaining < 0, ResponseStatus::BAD_REQUEST->value, 'Cannot pay(or discount) more than the order amount');

            $attributes['updated_by'] = request()->user()->id;
            $attributes['user_id'] = request()->user()->id;
            $createdAt = new Carbon($attributes['created_at']);
            $now = now();
            $createdAt->addHour($now->hour);
            $createdAt->addMinute($now->minute);
            $createdAt->second($now->second);
            $attributes['created_at'] = $createdAt;


            $status = OrderStatus::PENDING->value;
            if ($remaining == 0) $status = OrderStatus::PAID->value;
            else if ($attributes['paid'] ?? false) $status = OrderStatus::PARTIALLY_PAID->value;

            $orderData = collect([
                'amount' => $amount,
                'status' => $status,
                ...$attributes
            ])->except(['a_items'])->toArray();
            if ($order == null) $order = Order::create($orderData);
            else $order->update($orderData);
            DB::connection('tenant')->table('a_item_order')->insert(array_map(function ($aItem) use ($order) {
                return [
                    'a_item_id' => $aItem['id'],
                    'order_id' => $order->id,
                    'quantity' => $aItem['quantity'],
                    'price' => $aItem['price'],
                    'discount' => $aItem['discount'] ?? 0,
                    'name' => $aItem['name'],
                    'purchase_price' => $aItem['purchase_price'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $attributes['a_items']));

            $order->fresh()->aItems->each(function ($item) {
                DB::connection('tenant')->table('a_items')->where([
                    'id' => $item->id,
                    'type' => ProductType::STOCKED->value
                ])->decrement('stock', $item->pivot->quantity);
            });
            return $order->load(['aItems', 'purchases']);
        });
        return response()->json([
            'order' => $data
        ]);
    }


    public function show(Order $order)
    {
        $order->load(['aItems', 'purchases']);

        return response()->json([
            'order' => $order
        ]);
    }
}
