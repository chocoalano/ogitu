<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class OrderChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Pesanan Sistem';

    protected static ?int $sort = 3;

    public ?string $filter = '30days';

    protected function getData(): array
    {
        $filter = $this->filter;

        // Determine date range and grouping based on filter
        [$startDate, $endDate, $groupBy] = $this->getDateRangeAndGrouping($filter);

        // Get orders data
        $ordersCreatedData = $this->getOrdersCreatedData($startDate, $endDate, $groupBy);
        $ordersCompletedData = $this->getOrdersCompletedData($startDate, $endDate, $groupBy);
        $ordersPendingData = $this->getOrdersPendingData($startDate, $endDate, $groupBy);
        $labels = $ordersCreatedData['labels'];

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan Dibuat',
                    'data' => $ordersCreatedData['data'],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pesanan Selesai',
                    'data' => $ordersCompletedData['data'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pesanan Pending',
                    'data' => $ordersPendingData['data'],
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
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

    protected function getDateRangeAndGrouping(string $filter): array
    {
        $startDate = null;
        $endDate = now()->endOfDay();
        $groupBy = 'day';

        if ($filter === '7days') {
            $startDate = now()->subDays(7)->startOfDay();
            $groupBy = 'day';
        } elseif ($filter === '30days') {
            $startDate = now()->subDays(30)->startOfDay();
            $groupBy = 'day';
        } elseif ($filter === '90days') {
            $startDate = now()->subDays(90)->startOfDay();
            $groupBy = 'week';
        } elseif ($filter === '12months') {
            $startDate = now()->subMonths(12)->startOfMonth();
            $groupBy = 'month';
        }

        return [$startDate, $endDate, $groupBy];
    }

    protected function getOrdersCreatedData(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $labels = [];
        $data = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);

            foreach ($period as $date) {
                $count = Order::whereDate('created_at', $date)->count();

                $labels[] = $date->format('d M');
                $data[] = $count;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $count = Order::whereBetween('created_at', [$weekStart, $weekEnd])->count();

                $labels[] = $weekStart->format('d M');
                $data[] = $count;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $count = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();

                $labels[] = $date->format('M Y');
                $data[] = $count;
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    protected function getOrdersCompletedData(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $data = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);

            foreach ($period as $date) {
                // Count orders with status 'completed' or 'delivered'
                $count = Order::whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'delivered'])
                    ->count();

                $data[] = $count;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $count = Order::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('status', ['completed', 'delivered'])
                    ->count();

                $data[] = $count;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $count = Order::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['completed', 'delivered'])
                    ->count();

                $data[] = $count;
            }
        }

        return ['data' => $data];
    }

    protected function getOrdersPendingData(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $data = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);

            foreach ($period as $date) {
                // Count orders with status 'pending' or 'processing'
                $count = Order::whereDate('created_at', $date)
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();

                $data[] = $count;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $count = Order::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();

                $data[] = $count;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $count = Order::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();

                $data[] = $count;
            }
        }

        return ['data' => $data];
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
                                label += context.parsed.y + " pesanan";
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
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Pesanan',
                    ],
                    'ticks' => [
                        'stepSize' => 1,
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
