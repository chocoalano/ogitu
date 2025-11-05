<?php

namespace App\Filament\Store\Widgets;

use App\Models\OrderShop;
use App\Models\Shop;
use App\Models\VendorListing;
use App\Models\WalletAccount;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return [];
        }

        // Get vendor and shop for this customer
        $vendor = $customer->vendors()->first();

        if (! $vendor) {
            return [
                Stat::make('Info', 'Belum terdaftar sebagai vendor')
                    ->description('Silakan daftar sebagai vendor terlebih dahulu')
                    ->color('warning'),
            ];
        }

        $shop = $vendor->shops()->first();

        if (! $shop) {
            return [
                Stat::make('Info', 'Belum memiliki toko')
                    ->description('Silakan buat toko terlebih dahulu')
                    ->color('warning'),
            ];
        }

        // Get wallet balance
        $walletAccount = WalletAccount::where('owner_type', 'shop')
            ->where('owner_id', $shop->id)
            ->first();

        $walletBalance = $walletAccount ? $walletAccount->balance : 0;

        // Get total sales this month
        $totalSalesThisMonth = OrderShop::where('shop_id', $shop->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['processing', 'shipped', 'delivered', 'completed'])
            ->sum('subtotal');

        // Get total sales last month for comparison
        $totalSalesLastMonth = OrderShop::where('shop_id', $shop->id)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereIn('status', ['processing', 'shipped', 'delivered', 'completed'])
            ->sum('subtotal');

        // Calculate sales percentage change
        $salesChange = 0;
        $salesTrend = 'flat';

        if ($totalSalesLastMonth > 0) {
            $salesChange = (($totalSalesThisMonth - $totalSalesLastMonth) / $totalSalesLastMonth) * 100;
            $salesTrend = $salesChange > 0 ? 'up' : ($salesChange < 0 ? 'down' : 'flat');
        } elseif ($totalSalesThisMonth > 0) {
            $salesChange = 100;
            $salesTrend = 'up';
        }

        // Get total products/listings
        $totalProducts = VendorListing::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->count();

        // Get total products last month for comparison
        $totalProductsLastMonth = VendorListing::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->where('created_at', '<', now()->startOfMonth())
            ->count();

        // Calculate products change
        $productsChange = $totalProducts - $totalProductsLastMonth;
        $productsTrend = $productsChange > 0 ? 'up' : ($productsChange < 0 ? 'down' : 'flat');

        // Get pending orders count
        $pendingOrders = OrderShop::where('shop_id', $shop->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        return [
            Stat::make('Saldo E-wallet', 'Rp '.number_format($walletBalance, 0, ',', '.'))
                ->description('Saldo toko tersedia')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success')
                ->chart([0, $walletBalance / 10, $walletBalance / 5, $walletBalance]),

            Stat::make('Penjualan Bulan Ini', 'Rp '.number_format($totalSalesThisMonth, 0, ',', '.'))
                ->description(
                    $salesTrend === 'flat'
                        ? 'Tidak ada perubahan'
                        : abs(round($salesChange, 1)).'% '.($salesTrend === 'up' ? 'naik' : 'turun').' dari bulan lalu'
                )
                ->descriptionIcon(
                    $salesTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($salesTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus')
                )
                ->color($salesTrend === 'up' ? 'success' : ($salesTrend === 'down' ? 'danger' : 'gray'))
                ->chart([
                    $totalSalesLastMonth / 4,
                    $totalSalesLastMonth / 2,
                    $totalSalesLastMonth,
                    $totalSalesThisMonth,
                ]),

            Stat::make('Total Produk Aktif', number_format($totalProducts, 0, ',', '.'))
                ->description(
                    $productsTrend === 'flat'
                        ? 'Tidak ada perubahan'
                        : abs($productsChange).' produk '.($productsTrend === 'up' ? 'bertambah' : 'berkurang')
                )
                ->descriptionIcon(
                    $productsTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($productsTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus')
                )
                ->color($productsTrend === 'up' ? 'success' : ($productsTrend === 'down' ? 'warning' : 'gray')),

            Stat::make('Pesanan Pending', number_format($pendingOrders, 0, ',', '.'))
                ->description('Pesanan menunggu diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),
        ];
    }
}
