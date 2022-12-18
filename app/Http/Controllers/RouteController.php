<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function cart()
    {
        return Inertia::render('Cart');
    }

    public function checkout()
    {
        return Inertia::render('Checkout');
    }

    public function financialSummary()
    {
        request()->validate([
            'from' => ['sometimes', 'required', 'date'],
            'to' => ['required_with:from', 'date'],
        ]);
        $summary = [
            'orders' => Order::where([
                'status' => OrderStatus::COMPLETED->value
            ])->get(['amount', 'discount']),
            // 'purchases' => Purchase::with(['purchasable'])->whereRelation('purchasable', 'merchant_id', request()->user()->merchants()->first()->id)
            //     ->where('status', PurchaseStatus::NORMAL->value)
            //     ->get([
            //         'price', 'quantity'
            //     ])
        ];
        return Inertia::render('FinancialSummary', ['summary' => $summary]);
    }
}
