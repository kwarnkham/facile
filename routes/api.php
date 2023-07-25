<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\AItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DutyController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
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

Route::middleware('tenant')->group(function () {
    Route::controller(UserController::class)->prefix('/users')->group(function () {
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('user', 'user');
            Route::middleware(['role:admin'])->group(function () {
                Route::post('', 'store');
                Route::post('{user}/reset-password', 'resetPassword');
                Route::get('', 'index');
                Route::post('{user}/roles/{role}/toggle', 'toggleRole');
            });
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
            Route::put('{task}', 'update');
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


    Route::controller(RouteController::class)->group(function () {
        Route::get('settings', 'settings');
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::get('products/stock', 'productStocks');
        });
    });


    Route::controller(AItemController::class)->prefix('/a-items')->group(function () {
        Route::get('', 'index');
        Route::get('{aItem}', 'show');
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::post('', 'store');
            Route::put('{aItem}', 'update');
            Route::post('{aItem}/restock', 'restock');
            Route::post('{aItem}/toggle-status', 'toggleStatus');
        });
    });

    Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::post('{purchase}/cancel', 'cancel')->name('purchases.cancel');
            Route::post('{purchase}/group', 'group')->name('purchases.group');
            Route::get('', 'index')->name('purchases.index');
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


    Route::controller(OrderController::class)->prefix('/orders')->group(function () {
        Route::get('status', 'status')->name('orders.status');
        Route::middleware(['auth:sanctum', 'role:sale'])->group(function () {
            Route::get('', 'index');
            Route::get('{order}', 'show');
            Route::put('{order}', 'update');
            Route::post('record/{order?}', 'record');
            Route::post('{order}/purchase', 'purchase');
        });
    });

    Route::controller(TenantController::class)->prefix('/tenants')->group(function () {
        // Route::get('status', 'status')->name('orders.status');
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::post('', 'store');
            Route::get('', 'index');
            Route::post('{tenant}/renew-subscription', 'renewSubscription');
            Route::delete('{tenant}', 'destroy');
        });
    });

    Route::controller(PlanController::class)->prefix('/plans')->group(function () {
        // Route::get('status', 'status')->name('orders.status');
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::get('', 'index');
        });
    });

    Route::controller(CorrectionController::class)->prefix('/corrections')->group(function () {
        Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
            Route::post('', 'store');
            Route::get('', 'index');
        });
    });
});
