<?php

use App\Http\Controllers\DiscountController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MerchantPaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WholesaleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('index');

Route::get('/foo', fn () => 'bar');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::controller(ItemController::class)->prefix('/items')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('items.store');
        Route::get('create', 'create')->name('items.create');
        Route::get('{item}/edit', 'edit')->name('items.edit');
        Route::put('{item}', 'update')->name('items.update');
    });
    Route::get('', 'index')->name('items.index');
    Route::get('{item}', 'show')->name('items.show');
});

Route::controller(PictureController::class)->prefix('/pictures')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('pictures.store');
        Route::delete('{picture}', 'destroy')->name('pictures.destroy');
    });
});

Route::controller(TagController::class)->prefix('/tags')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('tags.store');
        Route::post('{tag}/attach', 'toggle')->name('tags.toggle');
    });
});

Route::controller(DiscountController::class)->prefix('/discounts')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('discounts.store');
    });
});

Route::controller(FeatureController::class)->prefix('/features')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('features.store');
        Route::get('create', 'create')->name('features.create');
        Route::get('', 'index')->name('features.index');
        Route::get('{feature}/edit', 'edit')->name('features.edit');
        Route::put('{feature}', 'update')->name('features.update');
        Route::get('{feature}', 'show')->name('features.show');
        Route::post('{feature}/discount', 'discount')->name('features.discount');
        Route::post('{feature}/restock', 'restock')->name('features.restock');
    });
});

Route::controller(MerchantPaymentController::class)->prefix('/merchant-payments')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('merchant_payments.store');
        Route::get('', 'index')->name('merchant_payments.index');
        Route::post('{merchantPayment}', 'toggle')->name('merchant_payments.toggle');
    });
});

Route::controller(WholesaleController::class)->prefix('/wholesales')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('wholesales.store');
        Route::get('create', 'create')->name('wholesales.create');
        Route::get('', 'index')->name('wholesales.index');
        Route::get('{wholesale}/edit', 'edit')->name('wholesales.edit');
        Route::put('{wholesale}', 'update')->name('wholesales.update');
    });
});

Route::controller(OrderController::class)->prefix('/orders')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('orders.store');
        Route::get('', 'index')->name('orders.index');
        Route::get('{order}', 'show')->name('orders.show');
        Route::post('{order}/pay', 'pay')->name('orders.pay');
        Route::post('{order}/complete', 'complete')->name('orders.complete');
        Route::post('{order}/cancel', 'cancel')->name('orders.cancel');
    });
});

Route::controller(PurchaseController::class)->prefix('/purchases')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('purchases.store');
    });
});

Route::controller(RouteController::class)->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::get('cart', 'cart')->name('routes.cart');
        Route::get('checkout', 'checkout')->name('routes.checkout');
    });
});

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

require __DIR__ . '/auth.php';
