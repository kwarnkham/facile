<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\Product;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function productStocks()
    {
        $data = request()->validate([
            'max_stock' => ['sometimes', 'numeric']
        ]);
        return response()->json([
            'data' => DB::select(
                'SELECT a.product_id,products.name as product,items.name as item,products.stock,a.sales FROM(SELECT product_id,SUM(quantity)AS sales FROM order_product GROUP BY product_id)AS a CROSS JOIN products ON products.id=a.product_id INNER JOIN items ON products.item_id=items.id WHERE products.stock<=? ORDER BY a.sales DESC',
                [$data['max_stock'] ?? 10]
            )
        ]);
    }

    public function settings()
    {
        $setting = Setting::first();
        return response()->json(['setting' => $setting]);
    }
}
