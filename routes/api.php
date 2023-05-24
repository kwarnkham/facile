<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\AItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DutyController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
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
    return response()->json(['user' => $request->user()->load(['roles'])]);
});

Route::controller(UserController::class)->prefix('/users')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store')->name('users.store');
        Route::post('{user}/reset-password', 'resetPassword');
        Route::get('', 'index')->name('users.index');
        Route::post('{user}/roles/{role}/toggle', 'toggleRole');
    });
});

Route::controller(AbsenceController::class)->prefix('/absences')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::delete('{absence}', 'destroy');
        Route::get('', 'index');
    });
});

Route::controller(OvertimeController::class)->prefix('/overtimes')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::delete('{overtime}', 'destroy');
        Route::get('', 'index');
    });
});

Route::controller(TaskController::class)->prefix('/tasks')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::delete('{task}', 'destroy');
        Route::get('{task}', 'show');
        Route::get('', 'index');
    });
});

Route::controller(DutyController::class)->prefix('/duties')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::delete('{duty}', 'destroy');
        Route::put('{duty}', 'update');
        Route::get('', 'index');
    });
});

Route::controller(RoleController::class)->prefix('/roles')->group(function () {
    Route::get('', 'index');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('{role}/tasks/toggle/{task}', 'toggleTask');
    });
});

Route::controller(SettingController::class)->prefix('/settings')->group(function () {
    Route::get('{setting}', 'show');
    Route::put('{setting}', 'update');
});

Route::controller(PictureController::class)->prefix('/pictures')->group(function () {
    Route::middleware(['auth:sanctum', 'role:sale'])->group(function () {
        Route::post('', 'store')->name('pictures.store');
        Route::delete('{picture}', 'destroy')->name('pictures.destroy');
    });
});

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('change-password', 'changePassword');
        Route::post('logout', 'logout');
    });
});

Route::controller(ItemController::class)->prefix('/items')->group(function () {
    Route::get('', 'index')->name('items.index');
    Route::get('{item}', 'show')->name('items.show');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store')->name('items.store');
        Route::put('{item}', 'update')->name('items.update');
    });
});

Route::controller(RouteController::class)->group(function () {
    Route::get('settings', 'settings');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('products/stock', 'productStocks');
    });
});

Route::controller(ProductController::class)->prefix('/products')->group(function () {
    Route::get('', 'index')->name('products.index');
    Route::get('{product}', 'show')->name('products.show');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store')->name('products.store');
        Route::put('{product}', 'update')->name('products.update');
        Route::post('{product}/restock', 'restock')->name('products.restock');
    });
});

Route::controller(AItemController::class)->prefix('/a-items')->group(function () {
    Route::get('', 'index');
    Route::get('{aItem}', 'show');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store');
        Route::put('{aItem}', 'update');
        Route::post('{aItem}/restock', 'restock');
    });
});

Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('{purchase}/cancel', 'cancel')->name('purchases.cancel');
        Route::post('{purchase}/group', 'group')->name('purchases.group');
        Route::get('', 'index')->name('purchases.index');
    });
});

Route::controller(BatchController::class)->prefix('/batches')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('{batch}/correct', 'correct')->name('batches.correct');
    });
});


Route::controller(ExpenseController::class)->prefix('/expenses')->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store')->name('expenses.store');
        Route::put('{expense}', 'update')->name('expenses.update');
        Route::get('', 'index')->name('expenses.index');
        Route::post('{expense}/record', 'record')->name('expenses.record');
    });
});

Route::controller(PaymentController::class)->prefix('/payments')->group(function () {
    Route::get('', 'index')->name('payments.index');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/{payment}/toggle', 'toggle')->name('payments.toggle');
        Route::post('', 'store')->name('payments.store');
        Route::put('/{payment}', 'update')->name('payments.update');
    });
});

Route::controller(PaymentTypeController::class)->prefix('/payment-types')->group(function () {
    Route::get('', 'index');
});

Route::controller(ServiceController::class)->prefix('/services')->group(function () {
    Route::get('', 'index')->name('services.index');
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('', 'store')->name('services.store');
        Route::put('{service}', 'update')->name('services.update');
    });
});

Route::controller(OrderController::class)->prefix('/orders')->group(function () {
    Route::get('status', 'status')->name('orders.status');
    Route::middleware(['auth:sanctum', 'role:sale'])->group(function () {
        Route::post('', 'store')->name('orders.store');
        Route::get('', 'index')->name('orders.index');
        Route::get('{order}', 'show')->name('orders.show');
        Route::post('{order}/pay', 'pay')->name('orders.pay');
        Route::put('{order}/customer', 'updateCustomer')->name('orders.update.customer');
        Route::put('{order}', 'update');
        Route::post('{order}/pack', 'pack')->name('orders.pack');
        Route::post('record/{order?}', 'record');
    });
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/pre-order', 'preOrder')->name('orders.preOrder');
        Route::post('{order}/complete', 'complete')->name('orders.complete');
        Route::post('{order}/cancel', 'cancel')->name('orders.cancel');
        Route::post('{order}/purchase', 'purchase');
    });
});
