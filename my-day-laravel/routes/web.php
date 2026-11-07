<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::view('/', 'my-day')->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/companion', [CompanionController::class, 'index']);
    Route::post('/companion/import', [CompanionController::class, 'import']);
    Route::post('/companion/tasks', [CompanionController::class, 'store']);
    Route::patch('/companion/tasks/{id}', [CompanionController::class, 'update'])->whereNumber('id');
    Route::delete('/companion/tasks/{id}', [CompanionController::class, 'destroy'])->whereNumber('id');
    Route::put('/companion/note', [CompanionController::class, 'note']);
});

Route::middleware('auth')->group(function () {
    Route::get('/news', [\App\Http\Controllers\NewsController::class, 'index'])->name('news.index');
    Route::post('/news/{article}/save', [\App\Http\Controllers\NewsController::class, 'save'])->name('news.save');
    Route::delete('/news/{article}/save', [\App\Http\Controllers\NewsController::class, 'unsave'])->name('news.unsave');
    Route::get('/news/{article}', [\App\Http\Controllers\NewsController::class, 'show'])->name('news.show');
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index']);
    Route::post('/reminders/{id}/read', [\App\Http\Controllers\ReminderController::class, 'read'])->whereNumber('id');
    Route::middleware(\App\Http\Middleware\EnsureAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AdminController::class, 'index'])->name('index');
        Route::get('/articles/create', [\App\Http\Controllers\AdminController::class, 'create'])->name('articles.create');
        Route::post('/articles', [\App\Http\Controllers\AdminController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}/edit', [\App\Http\Controllers\AdminController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article}', [\App\Http\Controllers\AdminController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article}', [\App\Http\Controllers\AdminController::class, 'destroy'])->name('articles.destroy');
    });
});
