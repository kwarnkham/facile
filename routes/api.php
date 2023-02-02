<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json(['user' => $request->user()]);
});

Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::middleware(['auth:sanctum'])
    ->post('logout', [AuthenticatedSessionController::class, 'destroy']);


Route::controller(ItemController::class)->prefix('/items')->group(function () {
    Route::get('', 'index');
    Route::get('{item}', 'show');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{item}', 'update');
    });
});

Route::controller(RouteController::class)->group(function () {
    Route::get('settings', 'settings');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('products/stock', 'productStocks');
    });
});

Route::controller(FeatureController::class)->prefix('/features')->group(function () {
    Route::get('', 'index');
    Route::get('{feature}', 'show');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{feature}', 'update');
        Route::post('{feature}/restock', 'restock');
    });
});

Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('{purchase}/cancel', 'cancel');
        Route::post('{purchase}/group', 'group')->name('purchases.group');
        Route::get('', 'index');
    });
});

Route::controller(BatchController::class)->prefix('/batches')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('{batch}/correct', 'correct');
    });
});


Route::controller(ExpenseController::class)->prefix('/expenses')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{expense}', 'update');
        Route::get('', 'index');
        Route::post('{expense}/record', 'record');
    });
});

Route::controller(PaymentController::class)->prefix('/payments')->group(function () {
    Route::get('', 'index');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/{payment}/toggle', 'toggle');
        Route::post('', 'store');
        Route::put('/{payment}', 'update');
    });
});

Route::controller(PaymentTypeController::class)->prefix('/payment-types')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('', 'index');
    });
});

Route::controller(ServiceController::class)->prefix('/services')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::get('', 'index');
        Route::put('{service}', 'update');
    });
});

Route::controller(OrderController::class)->prefix('/orders')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::post('/pre-order', 'preOrder');
        Route::get('', 'index');
        Route::get('status', 'status');
        Route::get('{order}', 'show');
        Route::post('{order}/pay', 'pay');
        Route::post('{order}/complete', 'complete');
        Route::post('{order}/cancel', 'cancel');
        Route::put('{order}/customer', 'updateCustomer')->name('orders.update.customer');
    });
});
