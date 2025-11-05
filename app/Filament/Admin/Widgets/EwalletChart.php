<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payout;
use App\Models\Topup;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EwalletChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Arus Kas E-wallet Sistem';

    protected static ?int $sort = 2;

    public ?string $filter = '30days';

    protected function getData(): array
    {
        $filter = $this->filter;

        // Determine date range and grouping based on filter
        [$startDate, $endDate, $groupBy] = $this->getDateRangeAndGrouping($filter);

        // Get all transactions data
        $moneyInData = $this->getMoneyInData($startDate, $endDate, $groupBy);
        $moneyOutData = $this->getMoneyOutData($startDate, $endDate, $groupBy);
        $netFlowData = [];
        $labels = $moneyInData['labels'];

        // Calculate net flow
        foreach ($moneyInData['data'] as $index => $inAmount) {
            $outAmount = $moneyOutData['data'][$index] ?? 0;
            $netFlowData[] = $inAmount - $outAmount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Uang Masuk (Rp)',
                    'data' => $moneyInData['data'],
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Uang Keluar (Rp)',
                    'data' => $moneyOutData['data'],
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Net Flow (Rp)',
                    'data' => $netFlowData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'fill' => false,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
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

    protected function getMoneyInData(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $labels = [];
        $data = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);

            foreach ($period as $date) {
                // Money IN from Topups (customer top-up their wallet)
                $topupAmount = Topup::whereDate('created_at', $date)
                    ->where('status', 'success')
                    ->sum('amount');

                // Money IN from Customer payments (orders paid)
                $orderPayments = DB::table('orders')
                    ->whereDate('created_at', $date)
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');

                $totalIn = $topupAmount + $orderPayments;

                $labels[] = $date->format('d M');
                $data[] = $totalIn;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $topupAmount = Topup::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->where('status', 'success')
                    ->sum('amount');

                $orderPayments = DB::table('orders')
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');

                $totalIn = $topupAmount + $orderPayments;

                $labels[] = $weekStart->format('d M');
                $data[] = $totalIn;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $topupAmount = Topup::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', 'success')
                    ->sum('amount');

                $orderPayments = DB::table('orders')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('payment_status', ['paid', 'partial'])
                    ->sum('grand_total');

                $totalIn = $topupAmount + $orderPayments;

                $labels[] = $date->format('M Y');
                $data[] = $totalIn;
            }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    protected function getMoneyOutData(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $data = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);

            foreach ($period as $date) {
                // Money OUT from Payouts (to vendors/shops)
                $payoutAmount = Payout::whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'paid'])
                    ->sum('net_amount');

                // Money OUT from Withdrawals
                $withdrawalAmount = Withdrawal::whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                // Money OUT from Refunds (if any)
                $refundAmount = DB::table('refunds')
                    ->whereDate('created_at', $date)
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                $totalOut = $payoutAmount + $withdrawalAmount + $refundAmount;
                $data[] = $totalOut;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $payoutAmount = Payout::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('status', ['completed', 'paid'])
                    ->sum('net_amount');

                $withdrawalAmount = Withdrawal::whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                $refundAmount = DB::table('refunds')
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                $totalOut = $payoutAmount + $withdrawalAmount + $refundAmount;
                $data[] = $totalOut;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $payoutAmount = Payout::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['completed', 'paid'])
                    ->sum('net_amount');

                $withdrawalAmount = Withdrawal::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                $refundAmount = DB::table('refunds')
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                $totalOut = $payoutAmount + $withdrawalAmount + $refundAmount;
                $data[] = $totalOut;
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
                                label += new Intl.NumberFormat("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                    minimumFractionDigits: 0
                                }).format(context.parsed.y);
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
                        'text' => 'Jumlah (Rp)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                                notation: "compact",
                                compactDisplay: "short"
                            }).format(value);
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
