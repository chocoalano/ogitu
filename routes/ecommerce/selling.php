<?php

use App\Http\Controllers\Ecommerce\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// untuk produk
Route::prefix('products')
    ->name('products.')
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('{slug}', 'show')->name('detail')->where('slug', '[A-Za-z0-9\-_.]+');
    });

// untuk account
Route::get('/carts', fn () => view('pages.account.cart-list'))->name('carts.list');
Route::get('/wishlist', fn () => view('pages.account.wishlist-list'))->name('wishlist.index');

// untuk auth:guest
Route::middleware('guest:customer')
    ->name('auth.')
    ->group(function () {
        Route::get('/login', fn () => view('pages.account.login'))->name('login');
        Route::get('/register', fn () => view('pages.account.register'))->name('register');
    });

// untuk auth
Route::middleware('auth:customer')
    ->name('auth.')
    ->group(function () {
        Route::post('/logout', function (Request $request) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        })->name('logout');

        Route::get('/profile', fn () => view('pages.account.profile'))->name('profile');
        Route::get('/orders', fn () => view('pages.account.orders'))->name('orders');
        Route::get('/orders/{id}', fn ($id) => view('pages.account.order_detail', ['id' => $id]))->name('orders.detail')->where('id', '[0-9]+');
    });

// untuk transaksi
Route::middleware('auth:customer')
    ->get('/checkout', fn () => view('pages.transaction.checkout'))
    ->name('checkout');
