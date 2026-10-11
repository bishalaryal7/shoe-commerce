<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffAccessController;
use App\Http\Controllers\WholesaleController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

Route::post('/language', [LanguageController::class, 'update'])->middleware('throttle:30,1')->name('language');

Route::get('/ready', function () {
    try {
        DB::select('SELECT 1');
        $ready = Schema::hasColumn('orders', 'payment_status')
            && Schema::hasTable('payment_attempts')
            && Schema::hasTable('order_emails')
            && Schema::hasTable('order_events')
            && Schema::hasTable('inventory_movements')
            && Schema::hasColumn('orders', 'offline_payment_reference')
            && Schema::hasColumn('products', 'name_ne')
            && Schema::hasColumn('products', 'description_ne')
            && Schema::hasColumn('users', 'phone_verified_at')
            && Schema::hasTable('social_accounts')
            && Schema::hasTable('phone_challenges');

        return response()->json(['status' => $ready ? 'ready' : 'unavailable'], $ready ? 200 : 503);
    } catch (Throwable $exception) {
        return response()->json(['status' => 'unavailable'], 503);
    }
})->name('readiness');
Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::post('/cart', [ShopController::class, 'add'])->name('cart.add');
Route::patch('/cart/{variant}', [ShopController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [ShopController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/orders/received/{reference}', [CheckoutController::class, 'received'])->name('order.received');
Route::get('/orders', [OrderController::class, 'index'])->middleware('auth')->name('orders.index');
Route::get('/orders/track/{reference}', [OrderController::class, 'show'])->name('orders.track');
Route::post('/orders/{reference}/pay', [PaymentController::class, 'start'])->middleware('throttle:5,1')->name('payments.start');
Route::post('/orders/{reference}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:5,1')->name('orders.cancel');
Route::post('/orders/{reference}/verify', [PaymentController::class, 'verify'])->middleware('throttle:10,1')->name('payments.verify');
Route::get('/payments/{attempt:reference}/return', [PaymentController::class, 'callback'])->middleware('throttle:30,1')->name('payments.return');
Route::get('/wholesale', [WholesaleController::class, 'index'])->name('wholesale');
Route::post('/wholesale', [WholesaleController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('wholesale.apply');
Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/team', [StaffAccessController::class, 'store'])->middleware('throttle:10,1')->name('team.store');
    Route::delete('/team/{user}', [StaffAccessController::class, 'destroy'])->middleware('throttle:10,1')->name('team.destroy');
    Route::patch('/variants/{variant}', [AdminController::class, 'stock'])->name('stock');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{product}/image', [ProductController::class, 'image'])->name('products.image');
    Route::patch('/products/{product}', [AdminController::class, 'product'])->name('product');
    Route::patch('/wholesale/{account}', [AdminController::class, 'approve'])->name('wholesale');
    Route::post('/orders/{order}/offline-payment', [AdminController::class, 'offlinePayment'])->name('orders.offline-payment');
    Route::post('/payments/{attempt}/verify', [AdminController::class, 'verifyPayment'])->name('payments.verify');
    Route::patch('/orders/{order}', [AdminController::class, 'order'])->name('order');
});
require __DIR__.'/settings.php';
require __DIR__.'/connections.php';
