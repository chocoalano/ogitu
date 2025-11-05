<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shop;
use App\Models\WalletAccount;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public ?string $filter = 'all';

    protected function getStats(): array
    {
        $filter = $this->filter;

        // Determine date range based on filter
        $startDate = null;
        $endDate = now();

        if ($filter === 'today') {
            $startDate = now()->startOfDay();
        } elseif ($filter === 'week') {
            $startDate = now()->startOfWeek();
        } elseif ($filter === 'month') {
            $startDate = now()->startOfMonth();
        } elseif ($filter === 'year') {
            $startDate = now()->startOfYear();
        }

        // === Total Orders ===
        $totalOrders = Order::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $previousPeriodOrders = $this->getPreviousPeriodCount(Order::class, $filter);
        $ordersChange = $this->calculateChange($totalOrders, $previousPeriodOrders);
        $ordersTrend = $this->determineTrend($ordersChange);

        // Get total revenue from orders
        $totalRevenue = Order::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->whereIn('payment_status', ['paid', 'partial'])
            ->sum('grand_total');

        // === Total Shops ===
        $totalShops = Shop::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $previousPeriodShops = $this->getPreviousPeriodCount(Shop::class, $filter);
        $shopsChange = $this->calculateChange($totalShops, $previousPeriodShops);
        $shopsTrend = $this->determineTrend($shopsChange);

        // Count active shops
        $activeShops = Shop::where('status', 'active')->count();

        // === Total Users (Customers) ===
        $totalCustomers = Customer::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->count();

        $previousPeriodCustomers = $this->getPreviousPeriodCount(Customer::class, $filter);
        $customersChange = $this->calculateChange($totalCustomers, $previousPeriodCustomers);
        $customersTrend = $this->determineTrend($customersChange);

        // Count active customers
        $activeCustomers = Customer::where('status', 'active')->count();

        // === Total E-wallet Balance ===
        $totalWalletBalance = WalletAccount::where('status', 'active')->sum('balance');

        $previousPeriodBalance = $this->getPreviousPeriodWalletBalance($filter);
        $balanceChange = $this->calculateChange($totalWalletBalance, $previousPeriodBalance);
        $balanceTrend = $this->determineTrend($balanceChange);

        // === Total Withdrawals ===
        $totalWithdrawals = Withdrawal::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->whereIn('status', ['completed', 'approved'])
            ->sum('amount');

        $totalWithdrawalsCount = Withdrawal::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->whereIn('status', ['completed', 'approved'])
            ->count();

        $previousPeriodWithdrawals = $this->getPreviousPeriodWithdrawals($filter);
        $withdrawalsChange = $this->calculateChange($totalWithdrawals, $previousPeriodWithdrawals);
        $withdrawalsTrend = $this->determineTrend($withdrawalsChange);

        // Count pending withdrawals
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

        return [
            Stat::make('Total Pesanan', number_format($totalOrders, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? 'Total pesanan sepanjang waktu'
                        : ($ordersTrend === 'flat'
                            ? 'Tidak ada perubahan'
                            : abs(round($ordersChange, 1)).'% '.($ordersTrend === 'up' ? 'naik' : 'turun'))
                )
                ->descriptionIcon(
                    $ordersTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($ordersTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus')
                )
                ->color($ordersTrend === 'up' ? 'success' : ($ordersTrend === 'down' ? 'danger' : 'gray'))
                ->chart($this->getOrdersChart($filter))
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? 'Total pendapatan sepanjang waktu'
                        : 'Dari pesanan yang dibayar'
                )
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getRevenueChart($filter)),

            Stat::make('Total Toko', number_format($totalShops, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? $activeShops.' toko aktif'
                        : ($shopsTrend === 'flat'
                            ? 'Tidak ada perubahan'
                            : abs(round($shopsChange, 1)).'% '.($shopsTrend === 'up' ? 'naik' : 'turun'))
                )
                ->descriptionIcon(
                    $shopsTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($shopsTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-building-storefront')
                )
                ->color($shopsTrend === 'up' ? 'success' : ($shopsTrend === 'down' ? 'warning' : 'info')),

            Stat::make('Total Pengguna', number_format($totalCustomers, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? $activeCustomers.' pengguna aktif'
                        : ($customersTrend === 'flat'
                            ? 'Tidak ada perubahan'
                            : abs(round($customersChange, 1)).'% '.($customersTrend === 'up' ? 'naik' : 'turun'))
                )
                ->descriptionIcon(
                    $customersTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($customersTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-users')
                )
                ->color($customersTrend === 'up' ? 'success' : ($customersTrend === 'down' ? 'danger' : 'info')),

            Stat::make('Total Saldo E-wallet', 'Rp '.number_format($totalWalletBalance, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? 'Saldo seluruh wallet aktif'
                        : ($balanceTrend === 'flat'
                            ? 'Tidak ada perubahan'
                            : abs(round($balanceChange, 1)).'% '.($balanceTrend === 'up' ? 'naik' : 'turun'))
                )
                ->descriptionIcon(
                    $balanceTrend === 'up'
                        ? 'heroicon-m-arrow-trending-up'
                        : ($balanceTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-wallet')
                )
                ->color($balanceTrend === 'up' ? 'success' : ($balanceTrend === 'down' ? 'warning' : 'info'))
                ->chart($this->getWalletBalanceChart($filter)),

            Stat::make('Total Withdrawal', 'Rp '.number_format($totalWithdrawals, 0, ',', '.'))
                ->description(
                    $filter === 'all'
                        ? $totalWithdrawalsCount.' withdrawal selesai'
                        : ($pendingWithdrawals > 0
                            ? $pendingWithdrawals.' withdrawal pending'
                            : ($withdrawalsTrend === 'flat'
                                ? 'Tidak ada perubahan'
                                : abs(round($withdrawalsChange, 1)).'% '.($withdrawalsTrend === 'up' ? 'naik' : 'turun')))
                )
                ->descriptionIcon(
                    $pendingWithdrawals > 0
                        ? 'heroicon-m-clock'
                        : ($withdrawalsTrend === 'up'
                            ? 'heroicon-m-arrow-trending-up'
                            : ($withdrawalsTrend === 'down' ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-banknotes'))
                )
                ->color(
                    $pendingWithdrawals > 0
                        ? 'warning'
                        : ($withdrawalsTrend === 'up' ? 'danger' : ($withdrawalsTrend === 'down' ? 'success' : 'info'))
                )
                ->chart($this->getWithdrawalsChart($filter)),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'Semua Waktu',
            'today' => 'Hari Ini',
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
        ];
    }

    protected function getPreviousPeriodCount(string $model, string $filter): int
    {
        if ($filter === 'all') {
            return 0;
        }

        $previousStart = null;
        $previousEnd = null;

        if ($filter === 'today') {
            $previousStart = now()->subDay()->startOfDay();
            $previousEnd = now()->subDay()->endOfDay();
        } elseif ($filter === 'week') {
            $previousStart = now()->subWeek()->startOfWeek();
            $previousEnd = now()->subWeek()->endOfWeek();
        } elseif ($filter === 'month') {
            $previousStart = now()->subMonth()->startOfMonth();
            $previousEnd = now()->subMonth()->endOfMonth();
        } elseif ($filter === 'year') {
            $previousStart = now()->subYear()->startOfYear();
            $previousEnd = now()->subYear()->endOfYear();
        }

        if (! $previousStart) {
            return 0;
        }

        return $model::whereBetween('created_at', [$previousStart, $previousEnd])->count();
    }

    protected function getPreviousPeriodWalletBalance(string $filter): float
    {
        if ($filter === 'all') {
            return 0;
        }

        // For simplicity, we'll use the current balance as base
        // In a real scenario, you'd track historical balance changes
        return WalletAccount::where('status', 'active')->sum('balance');
    }

    protected function getPreviousPeriodWithdrawals(string $filter): float
    {
        if ($filter === 'all') {
            return 0;
        }

        $previousStart = null;
        $previousEnd = null;

        if ($filter === 'today') {
            $previousStart = now()->subDay()->startOfDay();
            $previousEnd = now()->subDay()->endOfDay();
        } elseif ($filter === 'week') {
            $previousStart = now()->subWeek()->startOfWeek();
            $previousEnd = now()->subWeek()->endOfWeek();
        } elseif ($filter === 'month') {
            $previousStart = now()->subMonth()->startOfMonth();
            $previousEnd = now()->subMonth()->endOfMonth();
        } elseif ($filter === 'year') {
            $previousStart = now()->subYear()->startOfYear();
            $previousEnd = now()->subYear()->endOfYear();
        }

        if (! $previousStart) {
            return 0;
        }

        return Withdrawal::whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('status', ['completed', 'approved'])
            ->sum('amount');
    }

    protected function calculateChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function determineTrend(float $change): string
    {
        if ($change > 0) {
            return 'up';
        } elseif ($change < 0) {
            return 'down';
        }

        return 'flat';
    }

    protected function getOrdersChart(string $filter): array
    {
        if ($filter === 'all') {
            // Last 12 months
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = Order::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $data[] = $count;
            }

            return $data;
        }

        if ($filter === 'today') {
            // Last 24 hours
            $data = [];
            for ($i = 23; $i >= 0; $i--) {
                $hour = now()->subHours($i);
                $count = Order::whereBetween('created_at', [
                    $hour->copy()->startOfHour(),
                    $hour->copy()->endOfHour(),
                ])->count();
                $data[] = $count;
            }

            return $data;
        }

        if ($filter === 'week') {
            // Last 7 days
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = Order::whereDate('created_at', $date)->count();
                $data[] = $count;
            }

            return $data;
        }

        if ($filter === 'month') {
            // Last 30 days
            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $count = Order::whereDate('created_at', $date)->count();
                $data[] = $count;
            }

            return $data;
        }

        if ($filter === 'year') {
            // Last 12 months
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = Order::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $data[] = $count;
            }

            return $data;
        }

        return [];
    }

    protected function getRevenueChart(string $filter): array
    {
        if ($filter === 'all') {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $revenue = Order::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');
                $data[] = $revenue / 1000000; // Convert to millions for better chart display
            }

            return $data;
        }

        if ($filter === 'week') {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $revenue = Order::whereDate('created_at', $date)
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');
                $data[] = $revenue / 1000000;
            }

            return $data;
        }

        if ($filter === 'month') {
            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $revenue = Order::whereDate('created_at', $date)
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');
                $data[] = $revenue / 1000000;
            }

            return $data;
        }

        return [];
    }

    protected function getWalletBalanceChart(string $filter): array
    {
        // Simple representation - in real scenario, track historical balances
        $currentBalance = WalletAccount::where('status', 'active')->sum('balance');

        if ($filter === 'all') {
            return array_fill(0, 12, $currentBalance / 1000000);
        }

        if ($filter === 'week') {
            return array_fill(0, 7, $currentBalance / 1000000);
        }

        if ($filter === 'month') {
            return array_fill(0, 30, $currentBalance / 1000000);
        }

        return [$currentBalance / 1000000];
    }

    protected function getWithdrawalsChart(string $filter): array
    {
        if ($filter === 'all') {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $amount = Withdrawal::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');
                $data[] = $amount / 1000000; // Convert to millions for better chart display
            }

            return $data;
        }

        if ($filter === 'today') {
            $data = [];
            for ($i = 23; $i >= 0; $i--) {
                $hour = now()->subHours($i);
                $amount = Withdrawal::whereBetween('created_at', [
                    $hour->copy()->startOfHour(),
                    $hour->copy()->endOfHour(),
                ])
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');
                $data[] = $amount / 1000000;
            }

            return $data;
        }

        if ($filter === 'week') {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $amount = Withdrawal::whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');
                $data[] = $amount / 1000000;
            }

            return $data;
        }

        if ($filter === 'month') {
            $data = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $amount = Withdrawal::whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');
                $data[] = $amount / 1000000;
            }

            return $data;
        }

        if ($filter === 'year') {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $amount = Withdrawal::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');
                $data[] = $amount / 1000000;
            }

            return $data;
        }

        return [];
    }
}
