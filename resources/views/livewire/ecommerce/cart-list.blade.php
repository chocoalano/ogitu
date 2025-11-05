<section class="py-6 lg:py-10">
    <!-- Notification Messages -->
    @script
    <script>
        $wire.on('cart-success', (event) => {
            try {
                console.log(event.message || 'Berhasil!');
            } catch (e) {
                console.error('Notification error:', e);
            }
        });

        $wire.on('cart-error', (event) => {
            try {
                alert(event.message || 'Terjadi kesalahan!');
            } catch (e) {
                console.error('Notification error:', e);
            }
        });
    </script>
    @endscript

    <div class="container">
        @if(!$cart || $this->cartItems->isEmpty())
            <!-- Empty Cart State -->
            <div class="border rounded-lg border-default-200 p-12 text-center">
                <svg class="mx-auto h-24 w-24 text-default-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-default-800">Keranjang Anda Kosong</h3>
                <p class="mt-2 text-sm text-default-500">Mulai belanja untuk menambahkan item ke keranjang Anda.</p>
                <div class="mt-6">
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-primary bg-primary hover:bg-primary-500">
                        Mulai Belanja
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Cart Items Table -->
                <div class="col-span-1 lg:col-span-2">
                    <div class="border rounded-lg border-default-200">
                        <div class="px-6 py-5 border-b border-default-200">
                            <h4 class="text-lg font-medium text-default-800">Keranjang Belanja</h4>
                        </div>

                        <div class="flex flex-col overflow-hidden">
                            <div class="-m-1.5 overflow-x-auto">
                                <div class="p-1.5 min-w-full inline-block align-middle">
                                    <div class="overflow-hidden">
                                        <table class="min-w-full divide-y divide-default-200">
                                            <thead class="bg-default-400/10">
                                                <tr>
                                                    <th scope="col" class="min-w-56 px-5 py-3 text-start text-xs font-medium text-default-500 uppercase">Produk</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-medium uppercase text-start text-default-500">Harga</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-medium uppercase text-start text-default-500">Qty</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-medium text-center uppercase text-default-500">Sub-Total</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-default-200">
                                                @foreach($this->cartItems as $item)
                                                    @php
                                                        $listing = $item->vendor_listing;
                                                        $variant = $listing->product_variant ?? null;
                                                        $product = $variant->product ?? null;
                                                        $brand = $product->brand ?? null;
                                                        $itemTotal = $item->price_snapshot * $item->qty;
                                                        $image = $product?->media->first()?->url ?? '/images/placeholder.png';
                                                    @endphp

                                                    <tr wire:key="cart-item-{{ $item->id }}" class="relative">
                                                        <td class="px-5 py-3 whitespace-nowrap">
                                                            <div class="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    wire:click="removeItem({{ $item->id }})"
                                                                    wire:confirm="Apakah Anda yakin ingin menghapus item ini?"
                                                                    wire:loading.attr="disabled"
                                                                    class="text-default-400 hover:text-red-600 disabled:opacity-50"
                                                                >
                                                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                                                </button>
                                                                <img src="{{ $image }}" alt="{{ $product?->name }}" class="h-18 w-18 object-cover rounded">
                                                                <div>
                                                                    <h4 class="text-sm font-medium text-default-800">{{ $product?->name ?? 'Unknown Product' }}</h4>
                                                                    @if($brand)
                                                                        <p class="text-xs text-default-500">{{ $brand->name }}</p>
                                                                    @endif
                                                                    @if($item->variant_snapshot)
                                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                                            @foreach($item->variant_snapshot as $key => $value)
                                                                                <span class="text-xs text-default-500">{{ ucfirst($key) }}: {{ $value }}</span>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                    @if($listing->qty_available < 5)
                                                                        <p class="text-xs text-orange-600 mt-1">Stok: {{ $listing->qty_available }}</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-3 text-sm whitespace-nowrap text-default-800">
                                                            Rp {{ number_format($item->price_snapshot, 0, ',', '.') }}
                                                        </td>
                                                        <td class="px-5 py-3 whitespace-nowrap">
                                                            <div class="inline-flex justify-between p-1 border rounded-full border-default-200">
                                                                <button
                                                                    type="button"
                                                                    wire:click="updateQuantity({{ $item->id }}, {{ max(1, $item->qty - 1) }})"
                                                                    wire:loading.attr="disabled"
                                                                    class="inline-flex items-center justify-center shrink-0 w-6 h-6 text-sm rounded-full minus bg-default-200 text-default-800 disabled:opacity-50"
                                                                    {{ $item->qty <= 1 ? 'disabled' : '' }}
                                                                >–</button>
                                                                <input
                                                                    type="number"
                                                                    value="{{ $item->qty }}"
                                                                    min="1"
                                                                    max="{{ $listing->qty_available }}"
                                                                    class="w-8 p-0 text-sm text-center bg-transparent border-0 text-default-800 focus:ring-0"
                                                                    x-on:change="$wire.updateQuantity({{ $item->id }}, parseInt($event.target.value) || 1)"
                                                                >
                                                                <button
                                                                    type="button"
                                                                    wire:click="updateQuantity({{ $item->id }}, {{ $item->qty + 1 }})"
                                                                    wire:loading.attr="disabled"
                                                                    class="inline-flex items-center justify-center shrink-0 w-6 h-6 text-sm rounded-full plus bg-default-200 text-default-800 disabled:opacity-50"
                                                                    {{ $item->qty >= $listing->qty_available ? 'disabled' : '' }}
                                                                >+</button>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-3 text-sm text-center whitespace-nowrap text-default-800">
                                                            Rp {{ number_format($itemTotal, 0, ',', '.') }}
                                                        </td>
                                                    </tr>

                                                    <!-- Loading Overlay Row -->
                                                    <tr wire:loading wire:target="updateQuantity, removeItem" wire:key="loading-{{ $item->id }}" class="absolute inset-0 bg-white/70 pointer-events-none">
                                                        <td colspan="4" class="text-center py-4">
                                                            <svg class="animate-spin h-6 w-6 text-primary inline-block" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-5 border-t border-default-200">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <a href="/" class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-center transition-all duration-500 border rounded-full shadow-sm border-primary text-primary hover:bg-primary hover:text-white">
                                    Kembali belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Sidebar -->
                <div>
                    <!-- Total Keranjang -->
                    <div class="p-5 mb-5 border rounded-lg border-default-200">
                        <h4 class="mb-5 text-lg font-semibold text-default-800">Total Keranjang</h4>
                        <div class="mb-6">
                            <div class="flex justify-between mb-3">
                                <p class="text-sm text-default-500">Sub-total</p>
                                <p class="text-sm font-medium text-default-700">Rp {{ number_format($subtotal, 0, ',', '.') }}</p>
                            </div>
                            @if($discount > 0)
                                <div class="flex justify-between mb-3">
                                    <p class="text-sm text-default-500">Discount</p>
                                    <p class="text-sm font-medium text-green-600">- Rp {{ number_format($discount, 0, ',', '.') }}</p>
                                </div>
                            @endif
                            <div class="flex justify-between mb-3">
                                <p class="text-sm text-default-500">Tax (PPN 11%)</p>
                                <p class="text-sm font-medium text-default-700">Rp {{ number_format($tax, 0, ',', '.') }}</p>
                            </div>
                            <div class="my-4 border-b border-default-200"></div>
                            <div class="flex justify-between mb-3">
                                <p class="text-base text-default-700">Total</p>
                                <p class="text-base font-medium text-default-700">Rp {{ number_format($total, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <a href="/checkout" class="inline-flex items-center justify-center w-full px-10 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-primary bg-primary hover:bg-primary-500">
                            Lanjutkan ke Pembayaran
                        </a>
                    </div>

                    <!-- Kode Kupon -->
                    <div class="border rounded-lg border-default-200">
                        <div class="px-6 py-5 border-b border-default-200">
                            <h4 class="text-lg font-semibold text-default-800">Kode kupon</h4>
                        </div>
                        <div class="p-6">
                            <input
                                type="text"
                                wire:model="couponCode"
                                class="block w-full bg-transparent rounded-full py-2.5 px-4 border border-default-200 text-default-800 focus:ring-primary focus:border-primary"
                                placeholder="Masukan kode kupon disini."
                                @if($appliedCoupon) disabled @endif
                            >

                            @if($appliedCoupon)
                                <p class="mt-2 text-sm text-green-600">
                                    ✓ Kupon "{{ $appliedCoupon->code }}" diterapkan
                                </p>
                            @endif

                            <div class="flex justify-end gap-2 mt-4">
                                @if($appliedCoupon)
                                    <button
                                        type="button"
                                        wire:click="removeCoupon"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-red-600 bg-red-600 hover:bg-red-700 disabled:opacity-50"
                                    >
                                        Hapus Kupon
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="applyCoupon"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-primary bg-primary hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        Terapkan Kupon
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
