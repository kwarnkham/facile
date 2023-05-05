<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\AItem;
use App\Models\Item;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Order::query()->whereHas('payments')->with(['payments'])->get()->each(function ($order) {
            Model::withoutTimestamps(function () use ($order) {
                $order->update([
                    'paid' => $order->payments->reduce(function ($carry, $payment) {
                        return $carry + $payment->pivot->amount;
                    }, 0)
                ]);
            });
        });

        // delete from order_payment;
        // DB::table('order_payment')->truncate();

        Product::query()->get()->each(function ($product) {
            AItem::create([
                'name' => $product->name,
                'stock' => $product->stock,
                'price' => $product->price,
                'note' => $product->note,
                'type' => $product->type,
                'status' => ProductStatus::NORMAL->value
            ]);
        });

        Order::query()->whereHas('products')->with(['products'])->get()->each(function ($order) {
            $order->products->each(function ($product) use ($order) {
                $order->aItems()->attach([
                    AItem::where([
                        'name' => $product->name,
                        'type' => ProductType::STOCKED->value
                    ])->first()->id => [
                        'price' => $product->pivot->price,
                        'quantity' => $product->pivot->quantity,
                        'name' => $product->pivot->name,
                        'discount' => $product->pivot->discount,
                        'purchase_price' => $product->pivot->purchase_price,
                    ]
                ]);
            });
        });

        Item::query()->whereHas('orders')->with(['orders'])->get()->each(function ($item) {
            AItem::create([
                'name' => $item->name,
                'price' => $item->orders->first()->pivot->price,
                'stock' => 0,
                'type' => ProductType::UNSTOCKED->value,
                'status' => ProductStatus::NORMAL->value
            ]);
        });

        Order::query()->whereHas('items')->with(['items'])->get()->each(function ($order) {
            $order->items->each(function ($item) use ($order) {
                $order->aItems()->attach([
                    AItem::where([
                        'name' => $item->name,
                        'type' => ProductType::UNSTOCKED->value
                    ])->first()->id => [
                        'price' => $item->pivot->price,
                        'quantity' => $item->pivot->quantity,
                        'name' => $item->name,
                        'purchase_price' => 0
                    ]
                ]);
            });
        });

        Service::query()->whereHas('orders')->with(['orders'])->get()->each(function ($service) {
            AItem::create([
                'name' => $service->name,
                'price' => $service->orders->first()->pivot->price,
                'stock' => 0,
                'type' => ProductType::UNSTOCKED->value,
                'status' => ProductStatus::NORMAL->value
            ]);
        });

        Order::query()->whereHas('services')->with(['services'])->get()->each(function ($order) {
            $order->services->each(function ($service) use ($order) {
                $order->aItems()->attach([
                    AItem::where([
                        'name' => $service->name,
                        'type' => ProductType::UNSTOCKED->value
                    ])->first()->id => [
                        'price' => $service->pivot->price,
                        'quantity' => $service->pivot->quantity,
                        'name' => $service->name,
                        'purchase_price' => 0
                    ]
                ]);
            });
        });

        Purchase::query()->where('purchasable_type', 'App\\Models\\Product')->with(['purchasable'])->get()->each(function ($purchase) {
            $aItem = AItem::where([
                'name' => $purchase->purchasable->name,
                'type' => ProductType::STOCKED->value
            ])->first();
            Model::withoutTimestamps(function () use ($purchase, $aItem) {
                $purchase->update([
                    'purchasable_type' => 'App\\Models\\AItem',
                    'purchasable_id' => $aItem->id,
                ]);
            });
        });
    }
}
