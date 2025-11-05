<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
    <!-- Header Halaman -->
    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 leading-tight border-b border-gray-200 pb-3">
            Detail Pesanan
        </h1>
    </header>

    <!-- Ringkasan Status Pesanan dan Detail Utama -->
    <div class="bg-white shadow-xl rounded-xl p-6 mb-8 border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div class="mb-4 md:mb-0">
                <p class="text-sm font-medium text-gray-500 uppercase">ID Pesanan</p>
                <p class="text-2xl font-bold text-gray-900">#{{ $order->order_no }}</p>
                <p class="text-sm text-gray-500 mt-1">Dipesan pada {{ $order->created_at->format('d F Y') }}</p>
            </div>

            <div class="flex items-center gap-4">
                <!-- Badge Status Pesanan -->
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold {{ $this->getStatusBadgeClass($order->status) }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $this->getStatusLabel($order->status) }}
                </span>

                <!-- Badge Status Pembayaran -->
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold {{ $this->getPaymentStatusBadgeClass($order->payment_status) }}">
                    {{ $this->getPaymentStatusLabel($order->payment_status) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Bagian Utama (Produk, Pengiriman, Total) - Menggunakan Grid Responsif -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Kolom Kiri: Daftar Produk -->
        <div class="lg:col-span-2">
            @foreach($this->orderShops as $orderShop)
                <div class="bg-white shadow-xl rounded-xl border border-gray-100 mb-6">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ $orderShop->shop->name ?? 'Toko' }}</h2>
                                <p class="text-sm text-gray-500 mt-1">{{ $orderShop->shop->vendor->company_name ?? '' }} • {{ $orderShop->order_items->count() }} Produk</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $this->getStatusBadgeClass($orderShop->status) }}">
                                {{ $this->getStatusLabel($orderShop->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Tabel Daftar Produk (Responsive) -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Produk
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Kuantitas
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Harga Satuan
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($orderShop->order_items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="shrink-0 h-16 w-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                                                    @if($item->product_variant?->product?->media->first())
                                                        <img src="{{ $item->product_variant->product->media->first()->url }}"
                                                             alt="{{ $item->name }}"
                                                             class="h-full w-full object-cover">
                                                    @else
                                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                                    <div class="text-sm text-gray-500">
                                                        SKU: {{ $item->sku ?? '-' }}
                                                        @if($item->attributes)
                                                        {{-- {{ dd($item->attributes) }} --}}
                                                            @foreach($item->attributes ?? [] as $key => $value)
                                                                | {{ ucfirst($key) }}: {{ $value }}
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            {{ $item->qty }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                                            Rp {{ number_format($item->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Ringkasan per Toko -->
                    <div class="p-6 border-t border-gray-100 bg-gray-50">
                        <dl class="divide-y divide-gray-200">
                            <div class="flex justify-between py-2 text-sm text-gray-600">
                                <dt>Subtotal Produk</dt>
                                <dd class="font-medium">Rp {{ number_format($orderShop->subtotal, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex justify-between py-2 text-sm text-gray-600">
                                <dt>Biaya Pengiriman</dt>
                                <dd class="font-medium">Rp {{ number_format($orderShop->shipping_cost, 0, ',', '.') }}</dd>
                            </div>
                            @if($orderShop->discount_total > 0)
                                <div class="flex justify-between py-2 text-sm text-gray-600">
                                    <dt>Diskon</dt>
                                    <dd class="font-medium text-red-600">- Rp {{ number_format($orderShop->discount_total, 0, ',', '.') }}</dd>
                                </div>
                            @endif
                            @if($orderShop->tax_total > 0)
                                <div class="flex justify-between py-2 text-sm text-gray-600">
                                    <dt>Pajak</dt>
                                    <dd class="font-medium">Rp {{ number_format($orderShop->tax_total, 0, ',', '.') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($orderShop->shipments->first())
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-2">Informasi Pengiriman</p>
                                @php $shipment = $orderShop->shipments->first(); @endphp
                                <div class="text-sm text-gray-600">
                                    <p>Kurir: <span class="font-medium">{{ strtoupper($shipment->courier_code ?? '-') }} - {{ $shipment->service_name ?? '' }}</span></p>
                                    @if($shipment->tracking_no)
                                        <p>No. Resi: <span class="font-medium">{{ $shipment->tracking_no }}</span></p>
                                    @endif
                                    <p>Status: <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $this->getStatusBadgeClass($shipment->status) }}">{{ $this->getStatusLabel($shipment->status) }}</span></p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Kolom Kanan: Ringkasan & Detail Pelanggan -->
        <div class="lg:col-span-1 space-y-8">

            <!-- Card Ringkasan Total Pesanan -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Ringkasan Pesanan</h2>

                <dl class="divide-y divide-gray-100">
                    <div class="flex justify-between py-3 text-sm text-gray-600">
                        <dt>Subtotal Item</dt>
                        <dd class="font-medium">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between py-3 text-sm text-gray-600">
                        <dt>Biaya Pengiriman</dt>
                        <dd class="font-medium">Rp {{ number_format($order->shipping_total, 0, ',', '.') }}</dd>
                    </div>
                    @if($order->discount_total > 0)
                        <div class="flex justify-between py-3 text-sm text-gray-600">
                            <dt>Diskon/Voucher</dt>
                            <dd class="font-medium text-red-600">- Rp {{ number_format($order->discount_total, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if($order->tax_total > 0)
                        <div class="flex justify-between py-3 text-sm text-gray-600">
                            <dt>Pajak</dt>
                            <dd class="font-medium">Rp {{ number_format($order->tax_total, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    @if($order->wallet_used > 0)
                        <div class="flex justify-between py-3 text-sm text-gray-600">
                            <dt>Saldo Wallet Digunakan</dt>
                            <dd class="font-medium text-green-600">- Rp {{ number_format($order->wallet_used, 0, ',', '.') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between py-4 text-lg font-bold text-gray-900 border-t border-gray-200 mt-2">
                        <dt>Total Akhir</dt>
                        <dd>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Card Detail Pelanggan & Pengiriman -->
            <div class="bg-white shadow-xl rounded-xl border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Informasi Pelanggan</h2>

                <div class="space-y-4 text-sm">
                    <!-- Informasi Pelanggan -->
                    <div>
                        <p class="font-medium text-gray-700">Nama Pelanggan</p>
                        <p class="text-gray-500">{{ $order->customer->name }}</p>
                        <p class="text-gray-500">{{ $order->customer->email }}</p>
                        @if($order->customer->phone)
                            <p class="text-gray-500">{{ $order->customer->phone }}</p>
                        @endif
                    </div>

                    <!-- Alamat Pengiriman -->
                    @if($order->address)
                        <div>
                            <p class="font-medium text-gray-700">Alamat Pengiriman</p>
                            <p class="text-gray-500">{{ $order->address->recipient_name ?? $order->customer->name }}</p>
                            @if($order->address->phone)
                                <p class="text-gray-500">{{ $order->address->phone }}</p>
                            @endif
                            <p class="text-gray-500">{{ $order->address->line1 }}</p>
                            @if($order->address->line2)
                                <p class="text-gray-500">{{ $order->address->line2 }}</p>
                            @endif
                            <p class="text-gray-500">
                                {{ $order->address->city }}, {{ $order->address->state }}, {{ $order->address->postal_code }}
                            </p>
                        </div>
                    @endif

                    <!-- Metode Pembayaran -->
                    <div>
                        <p class="font-medium text-gray-700">Metode Pembayaran</p>
                        <div class="flex items-center text-gray-900">
                            <svg class="w-5 h-5 mr-2 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zm16 8a2 2 0 01-2 2H3a1 1 0 001 1h12a1 1 0 001-1v-1a1 1 0 100-2H4v2h16v-2z"></path>
                            </svg>
                            {{ $this->getPaymentMethodLabel($order->payment_method) }}
                        </div>
                    </div>

                    @if($order->notes)
                        <div>
                            <p class="font-medium text-gray-700">Catatan</p>
                            <p class="text-gray-500">{{ $order->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
