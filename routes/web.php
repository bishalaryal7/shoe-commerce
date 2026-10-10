<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WholesaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::post('/cart', [ShopController::class, 'add'])->name('cart.add');
Route::delete('/cart/{variant}', [ShopController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/orders/received/{reference}', [CheckoutController::class, 'received'])->name('order.received');
Route::get('/wholesale', [WholesaleController::class, 'index'])->name('wholesale');
Route::post('/wholesale', [WholesaleController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('wholesale.apply');
Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::patch('/variants/{variant}', [AdminController::class, 'stock'])->name('stock');
    Route::patch('/products/{product}', [AdminController::class, 'product'])->name('product');
    Route::patch('/wholesale/{account}', [AdminController::class, 'approve'])->name('wholesale');
    Route::patch('/orders/{order}', [AdminController::class, 'order'])->name('order');
});
require __DIR__.'/settings.php';
