<div class="space-y-8">
    {{-- Error/Success Messages --}}
    @script
    <script>
        $wire.on('checkout-error', (event) => {
            console.log(event.message);
        });

        $wire.on('checkout-success', (event) => {
            console.log(event.message);
        });

        $wire.on('topup-error', (event) => {
            console.log(event.message);
        });

        $wire.on('redirect-to-midtrans', (event) => {
            window.location.href = event.url;
        });
    </script>
    @endscript

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Shipping & Payment --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Shipping Information --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Informasi Pengiriman</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Nama Penerima <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="recipient_name" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                            @error('recipient_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Nomor Telepon</label>
                            <input type="text" wire:model="recipient_phone" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                            @error('recipient_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Tipe Penerima <span class="text-red-500">*</span></label>
                            <select wire:model="recipient_type" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                                <option value="individual">Perorangan</option>
                                <option value="company">Perusahaan</option>
                            </select>
                            @error('recipient_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                            <textarea wire:model="address_line1" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600"></textarea>
                            @error('address_line1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Detail Alamat (Opsional)</label>
                            <input type="text" wire:model="address_line2" placeholder="Blok, Unit, Lantai, dll" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Kota <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="city" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Provinsi</label>
                                <input type="text" wire:model="state" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Kode Pos</label>
                                <input type="text" wire:model="postal_code" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Negara <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="country_code" value="ID" disabled class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-100 dark:bg-gray-600 dark:border-gray-600">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Shipping Options --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Opsi Pengiriman</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Kurir</label>
                            <select wire:model="courier_code" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                                <option value="">Pilih Kurir</option>
                                <option value="jne">JNE</option>
                                <option value="tiki">TIKI</option>
                                <option value="pos">POS Indonesia</option>
                                <option value="jnt">J&T Express</option>
                                <option value="sicepat">SiCepat</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Layanan</label>
                            <select wire:model="service_name" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600">
                                <option value="">Pilih Layanan</option>
                                <option value="regular">Regular</option>
                                <option value="express">Express</option>
                                <option value="cargo">Cargo</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Additional Notes --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Catatan Tambahan</h2>
                    <textarea wire:model="notes" rows="4" placeholder="Catatan untuk penjual (opsional)" class="w-full px-4 py-2 border border-gray-200 rounded-lg dark:bg-gray-600 dark:border-gray-600"></textarea>
                </div>

                {{-- Payment Method --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4">Metode Pembayaran</h2>

                    <div class="space-y-4">
                        {{-- Wallet Payment --}}
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-primary-500 dark:border-gray-600 dark:hover:border-primary-500">
                            <input type="radio" wire:model="payment_method" value="wallet" class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">Saldo Wallet</div>
                                <div class="text-sm text-gray-500">Saldo Anda: Rp {{ number_format($wallet?->balance ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </label>

                        @if($payment_method === 'wallet' && $wallet && $wallet->balance < $grandTotal)
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                                    Saldo Anda tidak mencukupi. Silakan topup terlebih dahulu.
                                </p>
                                <button wire:click="$set('showTopupModal', true)" type="button" class="mt-2 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                    Topup Sekarang
                                </button>
                            </div>
                        @endif

                        {{-- Gateway Payment (Midtrans) --}}
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-primary-500 dark:border-gray-600 dark:hover:border-primary-500">
                            <input type="radio" wire:model="payment_method" value="gateway" class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">Payment Gateway (Midtrans)</div>
                                <div class="text-sm text-gray-500">Bayar dengan kartu kredit, transfer bank, e-wallet, dll.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Right Column: Order Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-xl font-semibold mb-4">Ringkasan Pesanan</h2>

                    {{-- Order Items --}}
                    <div class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                        @foreach($this->cartItems as $item)
                            <div class="flex gap-3">
                                <img src="{{ $item->vendor_listing->product_variant->product->media->first()?->url ?? 'https://via.placeholder.com/80' }}" alt="{{ $item->vendor_listing->product_variant->product->name }}" class="w-16 h-16 object-cover rounded">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ $item->vendor_listing->product_variant->product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $item->qty }}x Rp {{ number_format($item->price_snapshot, 0, ',', '.') }}</p>
                                    <p class="text-sm font-semibold">Rp {{ number_format($item->price_snapshot * $item->qty, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4 dark:border-gray-600">

                    {{-- Price Breakdown --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Biaya Pengiriman</span>
                            <span>Rp {{ number_format($shipping_cost, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Pajak (PPN 11%)</span>
                            <span>Rp {{ number_format($tax, 0, ',', '.') }}</span>
                        </div>

                        <hr class="my-2 dark:border-gray-600">

                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- Checkout Button --}}
                    <button wire:click="processCheckout" wire:loading.attr="disabled" type="button" class="inline-flex items-center justify-center w-full px-10 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-primary bg-primary hover:bg-primary-500">
                        <span wire:loading.remove>Proses Pesanan</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Topup Modal --}}
    @if($showTopupModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-semibold mb-4">Topup Saldo Wallet</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Jumlah Topup</label>
                        <input type="number" wire:model="topup_amount" min="10000" max="10000000" placeholder="Minimal Rp 10.000" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-600 dark:border-gray-600">
                        @error('topup_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-sm text-gray-500">
                        <p>Minimum: Rp 10.000</p>
                        <p>Maksimum: Rp 10.000.000</p>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('showTopupModal', false)" type="button" class="flex-1 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button wire:click="processTopup" type="button" class="flex-1 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <span wire:loading.remove wire:target="processTopup">Lanjutkan</span>
                            <span wire:loading wire:target="processTopup">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
