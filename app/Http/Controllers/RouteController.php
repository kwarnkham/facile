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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function cart()
    {
        $serviceSearch = request()->get('serviceSearch');
        $query = Service::take(5);
        if ($serviceSearch) $query->where('name', 'like', '%' . $serviceSearch . '%');
        $services = $query->get();
        return Inertia::render('Cart', ['toppings' => $services, 'serviceSearch' => $serviceSearch]);
    }

    public function checkout()
    {
        return Inertia::render('Checkout');
    }

    public function financialSummary()
    {
        $filters = request()->validate([
            'from' => ['date'],
            'to' => ['date'],
        ]);
        if (!array_key_exists('from', $filters)) {
            $filters['from'] = now()->startOfDay();
        } else {
            $filters['from'] = (new Carbon($filters['from']))->startOfDay();
        }
        if (!array_key_exists('to', $filters)) {
            $filters['to'] = now()->endOfDay();
        } else {
            $filters['to'] = (new Carbon($filters['to']))->endOfDay();
        }
        $summary = [
            'completed_orders' => Order::whereBetween('updated_at', [$filters['from'], $filters['to']])->where([
                'status' => OrderStatus::COMPLETED->value
            ])->get(['amount', 'discount']),
            'purchases' => Purchase::with(['purchasable'])
                ->whereBetween('updated_at', [$filters['from'], $filters['to']])
                ->where('status', PurchaseStatus::NORMAL->value)
                ->get()
        ];
        return Inertia::render('FinancialSummary', [
            'summary' => $summary,
            'filters' => [
                'from' => $filters['from'],
                'to' => $filters['to']->startOfDay(),
            ]
        ]);
    }

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

    public function stockSummery()
    {
        $products = Product::orderBy('stock')->paginate(request()->per_page ?? 50);
        return Inertia::render('StockSummary', ['products' => $products]);
    }

    public function settings()
    {
        $setting = Setting::first();
        return response()->json(['setting' => $setting]);
    }
}
