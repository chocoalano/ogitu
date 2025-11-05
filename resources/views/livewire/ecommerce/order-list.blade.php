<section class="py-6 lg:py-10">
    <div class="container">
        <!-- Header & Filters -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Pesanan Saya</h1>

            <!-- Search & Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nomor pesanan atau produk..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <select
                        wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Pembayaran</option>
                        <option value="processing">Diproses</option>
                        <option value="shipped">Dikirim</option>
                        <option value="delivered">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                        <option value="refunded">Dikembalikan</option>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div>
                    <select
                        wire:model.live="filterPayment"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Status Pembayaran</option>
                        <option value="unpaid">Belum Dibayar</option>
                        <option value="paid">Lunas</option>
                        <option value="refunded">Dikembalikan</option>
                        <option value="partial_refunded">Sebagian Dikembalikan</option>
                    </select>
                </div>
            </div>
        </div>

        @if($orders->isEmpty())
            <!-- Empty State -->
            <div class="border rounded-lg border-gray-200 p-12 text-center bg-white">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Belum Ada Pesanan</h3>
                <p class="mt-2 text-sm text-gray-500">Anda belum memiliki pesanan. Mulai belanja sekarang!</p>
                <div class="mt-6">
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-primary-600 rounded-full hover:bg-primary-700">
                        Mulai Belanja
                    </a>
                </div>
            </div>
        @else
            <!-- Orders List -->
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="overflow-hidden border rounded-lg border-gray-200 bg-white hover:shadow-md transition-shadow">
                        <div class="p-4 md:p-6">
                            <!-- Order Header -->
                            <div class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b border-gray-200">
                                <div class="mb-2 md:mb-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-semibold text-gray-900">#{{ $order->order_no }}</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getPaymentStatusBadgeClass($order->payment_status) }}">
                                            {{ $order->payment_status === 'paid' ? 'Lunas' : 'Belum Dibayar' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ $order->created_at->format('d F Y, H:i') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center border px-4 py-1 rounded-full text-sm font-semibold {{ $this->getStatusBadgeClass($order->status) }}">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $this->getStatusLabel($order->status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Order Items Preview -->
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-4">
                                        <!-- First Product Image -->
                                        @php
                                            $firstShop = $order->order_shops->first();
                                            $firstItem = $firstShop?->order_items->first();
                                            $product = $firstItem?->product_variant?->product;
                                        @endphp
                                        @if($product && $product->media->first())
                                            <img
                                                src="{{ $product->media->first()->url }}"
                                                alt="{{ $firstItem->name }}"
                                                class="w-20 h-20 lg:w-24 lg:h-24 object-cover rounded-lg">
                                        @else
                                            <div class="w-20 h-20 lg:w-24 lg:h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif

                                        <div class="flex-1">
                                            <h4 class="text-base lg:text-lg font-semibold text-gray-900 line-clamp-1">
                                                {{ $firstItem?->name ?? 'Produk' }}
                                            </h4>
                                            @if($firstItem?->product_variant?->product?->brand)
                                                <p class="text-sm text-gray-500">{{ $firstItem->product_variant->product->brand->name }}</p>
                                            @endif
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $order->total_qty ?? 0 }} item
                                                @if(($order->total_items ?? 0) > 1)
                                                    ({{ $order->total_items }} produk)
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price & Actions -->
                                <div class="w-full md:w-auto">
                                    <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                                        <div class="text-left md:text-right">
                                            <p class="text-xs text-gray-500 mb-1">Total Pembayaran</p>
                                            <h4 class="text-xl lg:text-2xl font-bold text-gray-900">
                                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                                            </h4>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-row md:flex-col gap-2">
                                            <a
                                                href="/orders/{{ $order->id }}"
                                                wire:navigate
                                                class="px-6 py-2 text-sm font-medium text-center text-white bg-purple-600 rounded-full hover:bg-primary-700 transition-colors">
                                                Lihat Detail
                                            </a>
                                            @if($order->status === 'pending' && $order->payment_status === 'unpaid')
                                                <button
                                                    type="button"
                                                    class="px-6 py-2 text-sm font-medium text-center text-primary-600 bg-gray-400 border border-primary-600 rounded-full hover:bg-primary-50 transition-colors">
                                                    Bayar Sekarang
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</section>
