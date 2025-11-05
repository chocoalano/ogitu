<?php

use App\Http\Controllers\Ecommerce\HomeController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/ecommerce/selling.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

// Midtrans payment callbacks
Route::controller(PaymentController::class)
    ->prefix('payment')
    ->name('payment.')
    ->group(function () {
        Route::get('finish', 'finish')->name('finish');
        Route::get('topup-finish', 'topupFinish')->name('topup-finish');
        Route::post('notification', 'notification')->name('notification');
    });

// articles
Route::prefix('articles')
    ->name('articles.')
    ->group(function () {
        Route::get('/', fn () => view('pages.articles.index'))->name('index');
        Route::get('/{slug}', fn ($slug) => view('pages.articles.show', ['slug' => $slug]))->name('show');
    });

// web pages (footer links, etc)
Route::get('/pages/{slug}', [App\Http\Controllers\WebPageController::class, 'show'])->name('pages.show');
