<?php

use App\Http\Controllers\Auth\PhoneAuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Settings\ConnectionsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('/auth/{provider}', [SocialAuthController::class, 'redirect'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:10,1')->name('social.redirect');
    Route::get('/login/phone', [PhoneAuthController::class, 'show'])->name('phone.login');
    Route::post('/login/phone/send', [PhoneAuthController::class, 'send'])->middleware('throttle:phone-send')->name('phone.send');
    Route::post('/login/phone/verify', [PhoneAuthController::class, 'verify'])->middleware('throttle:phone-verify')->name('phone.verify');
});
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:20,1')->name('social.callback');

Route::middleware('auth')->prefix('settings/connections')->name('connections.')->group(function () {
    Route::get('/', [ConnectionsController::class, 'index'])->name('index');
    Route::post('/password-reset', [ConnectionsController::class, 'resetPassword'])->middleware('throttle:3,10')->name('password-reset');
    Route::middleware('password.confirm')->group(function () {
        Route::post('/phone/send', [PhoneAuthController::class, 'send'])->middleware('throttle:phone-send')->name('phone.send');
        Route::post('/phone/verify', [PhoneAuthController::class, 'verify'])->middleware('throttle:phone-verify')->name('phone.verify');
        Route::post('/{provider}', [SocialAuthController::class, 'redirect'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:10,1')->name('social');
        Route::delete('/{provider}', [ConnectionsController::class, 'destroy'])->whereIn('provider', ['google', 'facebook', 'phone'])->name('destroy');
    });
});
