<?php

namespace App\Livewire\Ecommerce;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrderDetail extends Component
{
    public Order $order;

    public function mount($id): void
    {
        // Check authentication
        if (! Auth::guard('customer')->check()) {
            $this->redirect('/login', navigate: true);

            return;
        }

        $customerId = Auth::guard('customer')->id();

        // Load order with all necessary relationships in one go
        $this->order = Order::with([
            'customer:id,name,email,phone',
            'address:id,recipient_name,phone,line1,line2,city,state,postal_code,country_code',
            'order_shops',
            'order_shops.shop',
            'order_shops.shop.vendor',
            'order_shops.order_items',
            'order_shops.order_items.product_variant',
            'order_shops.order_items.product_variant.product',
            'order_shops.order_items.product_variant.product.brand',
            'order_shops.order_items.product_variant.product.media' => function ($query) {
                $query->limit(1);
            },
            'order_shops.shipments',
        ])
            ->where('customer_id', $customerId)
            ->findOrFail($id);

        // Verify ownership (already filtered by customer_id in query)
        if ($this->order->customer_id !== $customerId) {
            abort(403, 'Unauthorized access to order');
        }
    }

    #[Computed]
    public function orderShops()
    {
        // Simply return already loaded relationship
        return $this->order->order_shops;
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'delivered' => 'Telah Diterima',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan',
            default => ucfirst($status),
        };
    }

    public function getPaymentStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'unpaid' => 'bg-red-100 text-red-800',
            'paid' => 'bg-green-100 text-green-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            'partial_refunded' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'unpaid' => 'Belum Dibayar',
            'paid' => 'Lunas',
            'refunded' => 'Dikembalikan',
            'partial_refunded' => 'Sebagian Dikembalikan',
            default => ucfirst($status),
        };
    }

    public function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'wallet' => 'Saldo Wallet',
            'gateway' => 'Midtrans Payment Gateway',
            'cod' => 'Bayar di Tempat (COD)',
            default => ucfirst($method),
        };
    }

    public function render()
    {
        return view('livewire.ecommerce.order-detail');
    }
}
