<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\OrderShop;
use App\Models\Shop;
use App\View\Composers\FooterComposer;
use App\View\Composers\NavbarComposer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'customer' => Customer::class,
            'shop' => Shop::class,
            'order_shop' => OrderShop::class,
        ]);

        // Register view composers
        View::composer('layouts.partials.footer', FooterComposer::class);
        View::composer('livewire.ecommerce.navbar', NavbarComposer::class);
    }
}
