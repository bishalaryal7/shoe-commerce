<?php

use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSupportController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\CustomerNotificationController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductQuestionController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PromotionQuoteController;
use App\Http\Controllers\SavedProductsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SizeGuideController;
use App\Http\Controllers\StaffAccessController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\SupportController;
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
            && Schema::hasTable('phone_challenges')
            && Schema::hasTable('saved_products');
        $ready = $ready && Schema::hasTable('product_reviews')
            && Schema::hasTable('stock_alerts') && Schema::hasTable('promotions')
            && Schema::hasTable('product_questions') && Schema::hasTable('user_addresses')
            && Schema::hasColumn('orders', 'tracking_number')
            && Schema::hasColumn('product_reviews', 'seller_reply')
            && Schema::hasTable('product_review_comments')
            && Schema::hasTable('support_conversations')
            && Schema::hasTable('support_messages')
            && Schema::hasTable('notifications');

        return response()->json(['status' => $ready ? 'ready' : 'unavailable'], $ready ? 200 : 503);
    } catch (Throwable $exception) {
        return response()->json(['status' => 'unavailable'], 503);
    }
})->name('readiness');
Route::get('/', [ShopController::class, 'index'])->name('home');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product:slug}/stock-alerts', [StockAlertController::class, 'store'])->middleware('throttle:5,1')->name('products.stock-alerts');
Route::post('/products/{product:slug}/questions', [ProductQuestionController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('products.questions.store');
Route::get('/size-guide', SizeGuideController::class)->name('size-guide');
Route::post('/products/{product:slug}/reviews', [ProductReviewController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('products.reviews.store');
Route::post('/products/{product:slug}/reviews/{review}/helpful', [ProductReviewController::class, 'helpful'])->middleware(['auth', 'throttle:30,1'])->whereNumber('review')->name('products.reviews.helpful');
Route::post('/products/{product:slug}/reviews/{review}/comments', [ProductReviewController::class, 'comment'])->middleware(['auth', 'throttle:10,1'])->whereNumber('review')->name('products.reviews.comments');
Route::middleware('auth')->group(function () {
    Route::get('/messages', [SupportController::class, 'index'])->name('messages.index');
    Route::post('/messages', [SupportController::class, 'store'])->middleware('throttle:5,1')->name('messages.store');
    Route::post('/messages/{conversation}', [SupportController::class, 'reply'])->middleware('throttle:10,1')->name('messages.reply');
    Route::patch('/messages/{conversation}/close', [SupportController::class, 'close'])->name('messages.close');
    Route::get('/notifications', [CustomerNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [CustomerNotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [CustomerNotificationController::class, 'read'])->name('notifications.read');
});
Route::get('/saved', [SavedProductsController::class, 'index'])->name('saved.index');
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/{product}', [CompareController::class, 'toggle'])->middleware('throttle:30,1')->name('compare.toggle');
Route::delete('/compare', [CompareController::class, 'clear'])->middleware('throttle:10,1')->name('compare.clear');
Route::post('/saved/{product}', [SavedProductsController::class, 'toggle'])->middleware('throttle:30,1')->name('saved.toggle');
Route::delete('/recently-viewed', [SavedProductsController::class, 'clearHistory'])->middleware('throttle:10,1')->name('recently-viewed.clear');
Route::get('/cart', [ShopController::class, 'cart'])->name('cart');
Route::post('/cart', [ShopController::class, 'add'])->name('cart.add');
Route::patch('/cart/{variant}', [ShopController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [ShopController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CheckoutController::class, 'create'])->middleware('auth')->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('checkout.store');
Route::get('/checkout/promotion', PromotionQuoteController::class)->middleware(['auth', 'throttle:20,1'])->name('checkout.promotion');
Route::get('/orders/received/{reference}', [CheckoutController::class, 'received'])->name('order.received');
Route::get('/orders', [OrderController::class, 'index'])->middleware('auth')->name('orders.index');
Route::middleware('auth')->prefix('settings/addresses')->name('addresses.')->group(function () {
    Route::get('/', [AddressBookController::class, 'index'])->name('index');
    Route::post('/', [AddressBookController::class, 'store'])->name('store');
    Route::patch('/{address}', [AddressBookController::class, 'update'])->name('update');
    Route::delete('/{address}', [AddressBookController::class, 'destroy'])->name('destroy');
});
Route::post('/orders/{reference}/reorder', [OrderController::class, 'reorder'])->middleware(['auth', 'throttle:5,1'])->name('orders.reorder');
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
    Route::get('/inventory/export', [AdminController::class, 'exportInventory'])->name('inventory.export');
    Route::post('/inventory/import', [AdminController::class, 'importInventory'])->middleware('throttle:10,1')->name('inventory.import');
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
    Route::patch('/orders/{order}/shipping', [AdminController::class, 'shipping'])->name('orders.shipping');
    Route::post('/promotions', [AdminController::class, 'promotion'])->name('promotions.store');
    Route::patch('/promotions/{promotion}', [AdminController::class, 'togglePromotion'])->name('promotions.toggle');
    Route::patch('/questions/{question}', [ProductQuestionController::class, 'answer'])->name('questions.answer');
    Route::patch('/reviews/{review}', [ProductReviewController::class, 'reply'])->name('reviews.reply');
    Route::post('/messages/{conversation}', [AdminSupportController::class, 'reply'])->middleware('throttle:20,1')->name('messages.reply');
    Route::patch('/messages/{conversation}/status', [AdminSupportController::class, 'status'])->name('messages.status');
});
require __DIR__.'/settings.php';
require __DIR__.'/connections.php';
