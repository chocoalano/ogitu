<?php

namespace App\Livewire\Ecommerce;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OrderList extends Component
{
    use WithPagination;

    protected $listeners = ['orderUpdated' => '$refresh'];

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'payment')]
    public string $filterPayment = '';

    #[Url(as: 'search')]
    public string $search = '';

    public function mount(): void
    {
        // Check authentication
        if (! Auth::guard('customer')->check()) {
            $this->redirect('/login', navigate: true);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPayment(): void
    {
        $this->resetPage();
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-500',
            'processing' => 'bg-blue-100 text-blue-800 border-blue-500',
            'shipped' => 'bg-purple-100 text-purple-800 border-purple-500',
            'delivered' => 'bg-green-100 text-green-800 border-green-500',
            'cancelled' => 'bg-red-100 text-red-800 border-red-500',
            'refunded' => 'bg-gray-100 text-gray-800 border-gray-500',
            default => 'bg-gray-100 text-gray-800 border-gray-500',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'delivered' => 'Selesai',
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

    public function render()
    {
        $customerId = Auth::guard('customer')->id();

        $orders = Order::where('customer_id', $customerId)
            ->when($this->search, function ($query) {
                $query->where('order_no', 'like', '%'.$this->search.'%');
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterPayment, function ($query) {
                $query->where('payment_status', $this->filterPayment);
            })
            ->with([
                'order_shops' => function ($query) {
                    $query->with([
                        'order_items' => function ($q) {
                            $q->limit(1)
                                ->with([
                                    'product_variant.product.brand',
                                    'product_variant.product.media' => function ($m) {
                                        $m->limit(1);
                                    },
                                ]);
                        },
                    ])->limit(1);
                },
            ])
            ->withCount('order_items as total_items')
            ->withSum('order_items as total_qty', 'qty')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.ecommerce.order-list', [
            'orders' => $orders,
        ]);
    }
}
