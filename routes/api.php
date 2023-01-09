<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
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
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{item}', 'update');
        Route::get('', 'index');
        Route::get('{item}', 'show');
    });
});

Route::controller(FeatureController::class)->prefix('/features')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('all', 'all');
        Route::post('', 'store');
        // Route::get('create', 'create');
        Route::get('', 'index');
        // Route::get('{feature}/edit', 'edit')->name('features.edit');
        // Route::put('{feature}', 'update')->name('features.update');
        // Route::get('{feature}', 'show')->name('features.show');
        Route::post('{feature}/restock', 'restock')->name('features.restock');
    });
});

Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Route::post('', 'store')->name('purchases.store');
        Route::post('{purchase}/cancel', 'cancel');
        Route::get('', 'index');
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
