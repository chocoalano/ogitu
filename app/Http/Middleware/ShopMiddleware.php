<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek Otentikasi
        // Gunakan guard 'vendor' jika Anda telah mendefinisikannya, atau 'web' sebagai default.
        if (! auth('customer')->check()) {
            // Jika tidak terotentikasi, redirect ke halaman login vendor.
            return redirect()->route('filament.store.auth.login');
        } else {
            if (auth('customer')->user()->vendors == null) {
                return redirect()->route('filament.store.auth.register');
            }
        }

        // Jika vendor terotentikasi dan memiliki toko, lanjutkan request.
        return $next($request);
    }
}
