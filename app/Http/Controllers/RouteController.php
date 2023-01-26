<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\Feature;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Service;
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
                'SELECT a.feature_id,features.name as product,items.name as item,features.stock,a.sales FROM(SELECT feature_id,SUM(quantity)AS sales FROM feature_order GROUP BY feature_id)AS a CROSS JOIN features ON features.id=a.feature_id INNER JOIN items ON features.item_id=items.id WHERE features.stock<=? ORDER BY a.sales DESC',
                [$data['max_stock'] ?? 10]
            )
        ]);
    }

    public function stockSummery()
    {
        $features = Feature::orderBy('stock')->paginate(request()->per_page ?? 50);
        return Inertia::render('StockSummary', ['features' => $features]);
    }

    public function settings()
    {
        return response()->json([
            // spaces.madewithheart.tech/Facile/assets/production/print-logo.png
            'assets' => [
                'print_logo' => Storage::url(config('app')['name']) . '/assets/' . config('app')['env'] . '/print-logo.png',
                'delivery_logo' => Storage::url(config('app')['name']) . '/assets/' . config('app')['env'] . '/delivery-logo.png'
            ]
        ]);
    }
}
