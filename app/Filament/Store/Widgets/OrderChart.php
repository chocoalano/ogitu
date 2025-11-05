<?php

namespace App\Filament\Store\Widgets;

use App\Models\OrderShop;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OrderChart extends ChartWidget
{
    protected ?string $heading = 'Pesanan dan Penjualan';

    protected static ?int $sort = 2;

    public ?string $filter = '30days';

    protected function getData(): array
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $vendor = $customer->vendors()->first();

        if (! $vendor) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $shop = $vendor->shops()->first();

        if (! $shop) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $filter = $this->filter;

        // Determine date range based on filter
        if ($filter === '7days') {
            $startDate = now()->subDays(7);
            $endDate = now();
            $groupBy = 'day';
        } elseif ($filter === '30days') {
            $startDate = now()->subDays(30);
            $endDate = now();
            $groupBy = 'day';
        } elseif ($filter === '90days') {
            $startDate = now()->subDays(90);
            $endDate = now();
            $groupBy = 'week';
        } elseif ($filter === '12months') {
            $startDate = now()->subMonths(12);
            $endDate = now();
            $groupBy = 'month';
        } else {
            $startDate = now()->subDays(30);
            $endDate = now();
            $groupBy = 'day';
        }

        $orders = OrderShop::where('shop_id', $shop->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['processing', 'shipped', 'delivered', 'completed'])
            ->orderBy('created_at')
            ->get();

        // Prepare data based on grouping
        $salesData = [];
        $ordersCountData = [];
        $labels = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);
            foreach ($period as $date) {
                $dateKey = $date->format('Y-m-d');
                $dayOrders = $orders->filter(function ($order) use ($date) {
                    return $order->created_at->isSameDay($date);
                });

                $labels[] = $date->format('d M');
                $salesData[] = $dayOrders->sum('subtotal');
                $ordersCountData[] = $dayOrders->count();
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);
            foreach ($period as $date) {
                $weekStart = $date->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $weekOrders = $orders->filter(function ($order) use ($weekStart, $weekEnd) {
                    return $order->created_at->between($weekStart, $weekEnd);
                });

                $labels[] = $weekStart->format('d M');
                $salesData[] = $weekOrders->sum('subtotal');
                $ordersCountData[] = $weekOrders->count();
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);
            foreach ($period as $date) {
                $monthStart = $date->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthOrders = $orders->filter(function ($order) use ($monthStart, $monthEnd) {
                    return $order->created_at->between($monthStart, $monthEnd);
                });

                $labels[] = $date->format('M Y');
                $salesData[] = $monthOrders->sum('subtotal');
                $ordersCountData[] = $monthOrders->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan (Rp)',
                    'data' => $salesData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Jumlah Pesanan',
                    'data' => $ordersCountData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => '7 Hari Terakhir',
            '30days' => '30 Hari Terakhir',
            '90days' => '90 Hari Terakhir',
            '12months' => '12 Bulan Terakhir',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                if (context.dataset.label === "Penjualan (Rp)") {
                                    label += new Intl.NumberFormat("id-ID", {
                                        style: "currency",
                                        currency: "IDR",
                                        minimumFractionDigits: 0
                                    }).format(context.parsed.y);
                                } else {
                                    label += context.parsed.y + " pesanan";
                                }
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                                notation: "compact"
                            }).format(value);
                        }',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return value;
                        }',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
