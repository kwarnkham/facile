<?php

use App\Http\Controllers\FeatureController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPaymentController;
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

Route::controller(FeatureController::class)->prefix('/features')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('features.store');
        Route::get('create', 'create')->name('features.create');
        Route::get('', 'index')->name('features.index');
        Route::get('{feature}/edit', 'edit')->name('features.edit');
        Route::put('{feature}', 'update')->name('features.update');
        Route::get('{feature}', 'show')->name('features.show');
    });
});

Route::controller(UserPaymentController::class)->prefix('/user-payments')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('', 'store')->name('user_payments.store');
    });
});

Route::controller(OrderController::class)->prefix('/orders')->group(function () {
    Route::middleware(['auth', 'verified', 'role:merchant'])->group(function () {
        Route::post('{order}/pay', 'pay')->name('orders.pay');
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
