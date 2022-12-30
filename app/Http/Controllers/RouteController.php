<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\Feature;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function cart()
    {
        $toppingSearch = request()->get('toppingSearch');
        $query = Topping::take(5);
        if ($toppingSearch) $query->where('name', 'like', '%' . $toppingSearch . '%');
        $toppings = $query->get();
        return Inertia::render('Cart', ['toppings' => $toppings, 'toppingSearch' => $toppingSearch]);
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

    public function stockSummery()
    {
        $features = Feature::orderBy('stock')->paginate(request()->per_page ?? 50);
        return Inertia::render('StockSummary', ['features' => $features]);
    }
}
