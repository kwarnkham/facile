<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\UserController;
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
    });
    Route::get('', 'index')->name('items.index');
    Route::get('{item}', 'show')->name('items.show');
});

Route::middleware(['auth', 'verified', 'role:merchant'])->post('pictures', [PictureController::class, 'store'])->name('pictures.store');

Route::get('users', [UserController::class, 'index'])->name('users.index');

require __DIR__ . '/auth.php';
