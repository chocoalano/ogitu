<?php

namespace App\Filament\Store\Widgets;

use App\Models\LedgerEntry;
use App\Models\WalletAccount;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EwalletChart extends ChartWidget
{
    protected ?string $heading = 'Grafik Saldo E-wallet';

    protected static ?int $sort = 3;

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

        // Get wallet account for this shop
        $walletAccount = WalletAccount::where('owner_type', 'shop')
            ->where('owner_id', $shop->id)
            ->first();

        if (! $walletAccount) {
            return [
                'datasets' => [
                    [
                        'label' => 'Saldo (Rp)',
                        'data' => [0],
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => ['Tidak ada data'],
            ];
        }

        $filter = $this->filter;

        // Determine date range based on filter
        if ($filter === '7days') {
            $startDate = now()->subDays(7)->startOfDay();
            $endDate = now()->endOfDay();
            $groupBy = 'day';
        } elseif ($filter === '30days') {
            $startDate = now()->subDays(30)->startOfDay();
            $endDate = now()->endOfDay();
            $groupBy = 'day';
        } elseif ($filter === '90days') {
            $startDate = now()->subDays(90)->startOfDay();
            $endDate = now()->endOfDay();
            $groupBy = 'week';
        } elseif ($filter === '12months') {
            $startDate = now()->subMonths(12)->startOfMonth();
            $endDate = now()->endOfDay();
            $groupBy = 'month';
        } else {
            $startDate = now()->subDays(30)->startOfDay();
            $endDate = now()->endOfDay();
            $groupBy = 'day';
        }

        // Get all ledger entries for this wallet account
        $ledgerEntries = LedgerEntry::where('account_id', $walletAccount->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        // Get initial balance (balance before start date)
        $lastEntryBeforeStart = LedgerEntry::where('account_id', $walletAccount->id)
            ->where('created_at', '<', $startDate)
            ->orderBy('created_at', 'desc')
            ->first();

        $initialBalance = $lastEntryBeforeStart ? $lastEntryBeforeStart->balance_after : 0;

        // Prepare data based on grouping
        $balanceData = [];
        $debitData = [];
        $creditData = [];
        $labels = [];

        if ($groupBy === 'day') {
            $period = Carbon::parse($startDate)->daysUntil($endDate);
            $currentBalance = $initialBalance;

            foreach ($period as $date) {
                $dayEntries = $ledgerEntries->filter(function ($entry) use ($date) {
                    return $entry->created_at->isSameDay($date);
                });

                // Calculate balance for this day
                if ($dayEntries->count() > 0) {
                    $lastEntry = $dayEntries->sortByDesc('created_at')->first();
                    $currentBalance = $lastEntry->balance_after;
                }
                // If no transactions, balance remains the same (no change needed)

                // Calculate debit and credit for this day
                $dayDebit = $dayEntries->where('direction', 'debit')->sum('amount');
                $dayCredit = $dayEntries->where('direction', 'credit')->sum('amount');

                $labels[] = $date->format('d M');
                $balanceData[] = $currentBalance;
                $debitData[] = $dayDebit;
                $creditData[] = $dayCredit;
            }
        } elseif ($groupBy === 'week') {
            $period = Carbon::parse($startDate)->weeksUntil($endDate);
            $currentBalance = $initialBalance;

            foreach ($period as $date) {
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $weekEntries = $ledgerEntries->filter(function ($entry) use ($weekStart, $weekEnd) {
                    return $entry->created_at->between($weekStart, $weekEnd);
                });

                // Calculate balance for this week
                if ($weekEntries->count() > 0) {
                    $lastEntry = $weekEntries->sortByDesc('created_at')->first();
                    $currentBalance = $lastEntry->balance_after;
                }

                // Calculate debit and credit for this week
                $weekDebit = $weekEntries->where('direction', 'debit')->sum('amount');
                $weekCredit = $weekEntries->where('direction', 'credit')->sum('amount');

                $labels[] = $weekStart->format('d M');
                $balanceData[] = $currentBalance;
                $debitData[] = $weekDebit;
                $creditData[] = $weekCredit;
            }
        } elseif ($groupBy === 'month') {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);
            $currentBalance = $initialBalance;

            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthEntries = $ledgerEntries->filter(function ($entry) use ($monthStart, $monthEnd) {
                    return $entry->created_at->between($monthStart, $monthEnd);
                });

                // Calculate balance for this month
                if ($monthEntries->count() > 0) {
                    $lastEntry = $monthEntries->sortByDesc('created_at')->first();
                    $currentBalance = $lastEntry->balance_after;
                }

                // Calculate debit and credit for this month
                $monthDebit = $monthEntries->where('direction', 'debit')->sum('amount');
                $monthCredit = $monthEntries->where('direction', 'credit')->sum('amount');

                $labels[] = $date->format('M Y');
                $balanceData[] = $currentBalance;
                $debitData[] = $monthDebit;
                $creditData[] = $monthCredit;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Saldo (Rp)',
                    'data' => $balanceData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Pemasukan (Rp)',
                    'data' => $creditData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.3)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                    'type' => 'bar',
                ],
                [
                    'label' => 'Pengeluaran (Rp)',
                    'data' => $debitData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.3)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                    'type' => 'bar',
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
                        'text' => 'Saldo',
                    ],
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
                    'title' => [
                        'display' => true,
                        'text' => 'Transaksi',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
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
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
