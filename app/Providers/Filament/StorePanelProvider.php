<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Widgets\OgituWidget;
use App\Http\Middleware\ShopMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StorePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('store')
            ->path('store')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Hex('#D705F2'),
            ])
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Store/Resources'), for: 'App\Filament\Store\Resources')
            ->discoverPages(in: app_path('Filament/Store/Pages'), for: 'App\Filament\Store\Pages')
            ->discoverWidgets(in: app_path('Filament/Store/Widgets'), for: 'App\Filament\Store\Widgets')
            ->discoverLivewireComponents(in: app_path('Filament/Store/Components'), for: 'App\Filament\Store\Components')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Manajemen Produk')
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make()
                    ->label('Penjualan & Order')
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make()
                    ->label('Keuangan & Toko')
                    ->icon('heroicon-o-shopping-cart'),
                NavigationGroup::make()
                    ->label('Pengaturan Akun')
                    ->icon('heroicon-o-shopping-cart'),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
                OgituWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('customer')
            ->authMiddleware([
                Authenticate::class,
                ShopMiddleware::class,
            ]);
    }
}
