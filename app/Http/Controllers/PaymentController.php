<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Escrow;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Topup;
use App\Models\WalletAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function finish(Request $request)
    {
        $orderNo = $request->query('order_id');
        $statusCode = $request->query('status_code');
        $transactionStatus = $request->query('transaction_status');

        Log::info('Payment finish callback', [
            'order_no' => $orderNo,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
        ]);

        // Find order
        $order = Order::where('order_no', $orderNo)->first();

        if (! $order) {
            return redirect('/orders')->with('error', 'Pesanan tidak ditemukan');
        }

        // Check transaction status
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            try {
                DB::beginTransaction();

                // Update order payment status
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                // Process escrow for each order shop
                $orderShops = OrderShop::where('order_id', $order->id)->get();

                foreach ($orderShops as $orderShop) {
                    // Get or create shop wallet
                    $shopWallet = WalletAccount::firstOrCreate(
                        [
                            'owner_type' => 'shop',
                            'owner_id' => $orderShop->shop_id,
                        ],
                        [
                            'currency' => 'IDR',
                            'balance' => 0,
                            'status' => 'active',
                        ]
                    );

                    // Calculate shop amount (subtotal + tax - commission)
                    $shopAmount = $orderShop->subtotal + $orderShop->tax_total - $orderShop->commission_fee;

                    // Create escrow (hold payment until delivery)
                    $escrow = Escrow::create([
                        'order_shop_id' => $orderShop->id,
                        'wallet_account_id' => $shopWallet->id,
                        'amount_held' => $shopAmount,
                        'status' => 'held',
                    ]);

                    // Update order shop escrow_id and status
                    $orderShop->update([
                        'escrow_id' => $escrow->id,
                        'status' => 'awaiting_fulfillment',
                    ]);
                }

                // Clear customer cart
                $cart = Cart::where('customer_id', $order->customer_id)->first();
                if ($cart) {
                    $cart->cart_items()->delete();
                    $cart->delete();
                }

                DB::commit();

                return redirect("/orders/{$order->id}")->with('success', 'Pembayaran berhasil! Terima kasih atas pesanan Anda.');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Payment processing error: '.$e->getMessage());

                return redirect("/orders/{$order->id}")->with('error', 'Terjadi kesalahan saat memproses pembayaran.');
            }
        } elseif ($transactionStatus === 'pending') {
            return redirect("/orders/{$order->id}")->with('info', 'Pembayaran Anda sedang diproses. Mohon tunggu konfirmasi.');
        } else {
            // Failed, cancelled, or expired
            $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]);

            return redirect("/orders/{$order->id}")->with('error', 'Pembayaran gagal atau dibatalkan.');
        }
    }

    public function topupFinish(Request $request)
    {
        $topupId = $request->query('topup_id');
        $statusCode = $request->query('status_code');
        $transactionStatus = $request->query('transaction_status');

        Log::info('Topup finish callback', [
            'topup_id' => $topupId,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
        ]);

        // Find topup
        $topup = Topup::find($topupId);

        if (! $topup) {
            return redirect('/wallet')->with('error', 'Topup tidak ditemukan');
        }

        // Check transaction status
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            try {
                DB::beginTransaction();

                // Update topup status
                $topup->update(['status' => 'completed']);

                // Get customer wallet
                $wallet = WalletAccount::firstOrCreate(
                    [
                        'owner_type' => 'customer',
                        'owner_id' => $topup->customer_id,
                    ],
                    [
                        'currency' => 'IDR',
                        'balance' => 0,
                        'status' => 'active',
                    ]
                );

                // Add balance
                $wallet->increment('balance', $topup->amount);

                // Create ledger entry
                LedgerEntry::create([
                    'account_id' => $wallet->id,
                    'transaction_type' => 'topup',
                    'direction' => 'credit',
                    'amount' => $topup->amount,
                    'related_type' => 'topup',
                    'related_id' => $topup->id,
                    'status' => 'completed',
                    'description' => 'Topup saldo Rp '.number_format($topup->amount, 0, ',', '.'),
                ]);

                DB::commit();

                return redirect('/wallet')->with('success', 'Topup berhasil! Saldo Anda telah ditambahkan.');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Topup processing error: '.$e->getMessage());

                return redirect('/wallet')->with('error', 'Terjadi kesalahan saat memproses topup.');
            }
        } elseif ($transactionStatus === 'pending') {
            return redirect('/wallet')->with('info', 'Topup Anda sedang diproses. Mohon tunggu konfirmasi.');
        } else {
            // Failed, cancelled, or expired
            $topup->update(['status' => 'failed']);

            return redirect('/wallet')->with('error', 'Topup gagal atau dibatalkan.');
        }
    }

    public function notification(Request $request)
    {
        // Midtrans notification webhook
        try {
            // Configure Midtrans
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);

            $notification = new \Midtrans\Notification;

            $orderId = $notification->order_id;
            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;

            Log::info('Midtrans notification received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
            ]);

            // Check if it's a topup or order
            if (str_starts_with($orderId, 'TOPUP-')) {
                // Handle topup notification
                preg_match('/TOPUP-(\d+)-/', $orderId, $matches);
                $topupId = $matches[1] ?? null;

                if ($topupId) {
                    $topup = Topup::find($topupId);

                    if ($topup && $transactionStatus === 'settlement') {
                        DB::beginTransaction();

                        $topup->update(['status' => 'completed']);

                        $wallet = WalletAccount::where('owner_type', 'customer')
                            ->where('owner_id', $topup->customer_id)
                            ->first();

                        if ($wallet) {
                            $wallet->increment('balance', $topup->amount);

                            LedgerEntry::create([
                                'account_id' => $wallet->id,
                                'transaction_type' => 'topup',
                                'direction' => 'credit',
                                'amount' => $topup->amount,
                                'related_type' => 'topup',
                                'related_id' => $topup->id,
                                'status' => 'completed',
                                'description' => 'Topup saldo Rp '.number_format($topup->amount, 0, ',', '.'),
                            ]);
                        }

                        DB::commit();
                    }
                }
            } else {
                // Handle order notification
                $order = Order::where('order_no', $orderId)->first();

                if ($order && $transactionStatus === 'settlement') {
                    DB::beginTransaction();

                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                    ]);

                    $orderShops = OrderShop::where('order_id', $order->id)->get();

                    foreach ($orderShops as $orderShop) {
                        $shopWallet = WalletAccount::firstOrCreate(
                            [
                                'owner_type' => 'shop',
                                'owner_id' => $orderShop->shop_id,
                            ],
                            [
                                'currency' => 'IDR',
                                'balance' => 0,
                                'status' => 'active',
                            ]
                        );

                        $shopAmount = $orderShop->subtotal + $orderShop->tax_total - $orderShop->commission_fee;

                        $escrow = Escrow::create([
                            'order_shop_id' => $orderShop->id,
                            'wallet_account_id' => $shopWallet->id,
                            'amount_held' => $shopAmount,
                            'status' => 'held',
                        ]);

                        $orderShop->update([
                            'escrow_id' => $escrow->id,
                            'status' => 'awaiting_fulfillment',
                        ]);
                    }

                    DB::commit();
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Midtrans notification error: '.$e->getMessage());

            return response()->json(['status' => 'error'], 500);
        }
    }
}
