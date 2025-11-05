<div>
    {{-- VARIAN (dari product_variants) --}}
    @if(count($variantOptions) > 0)
        <div class="flex items-start gap-3 mb-8">
            <h4 class="mt-1 text-sm text-default-700 whitespace-nowrap">Pilihan Varian :</h4>

            <div class="flex flex-wrap gap-3 gap-y-6">
                @foreach($variantOptions as $var)
                    @php
                        $id = 'variant-' . ($var['id'] ?? '');
                        $checked = $variantId === ($var['id'] ?? null);
                    @endphp
                    <div>
                        <input type="radio" wire:model.live="variantId" id="{{ $id }}" value="{{ $var['id'] ?? '' }}"
                            class="hidden peer" @checked($checked) />
                        <label for="{{ $id }}"
                            class="px-4 py-2 text-sm text-center transition bg-default-100 rounded-full cursor-pointer select-none text-default-600 border border-default-200 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary hover:bg-default-200">
                            {{ $var['name'] ?? ('SKU: ' . ($var['sku'] ?? '')) }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- QTY + ACTION --}}
    <div class="flex items-center gap-2 mb-8">
        <div class="relative z-10 inline-flex justify-between p-1 border rounded-full border-default-200">
            <button type="button" wire:click="decrement"
                class="inline-flex items-center justify-center shrink-0 text-sm rounded-full bg-default-200 text-default-800 h-9 w-9 hover:bg-default-300">–</button>
            <input type="text" class="w-12 p-0 text-sm text-center bg-transparent border-0 focus:ring-0"
                wire:model="qty" readonly>
            <button type="button" wire:click="increment"
                class="inline-flex items-center justify-center shrink-0 text-sm rounded-full bg-default-200 text-default-800 h-9 w-9 hover:bg-default-300">+</button>
        </div>

        <button type="button" wire:click="addToCart" wire:loading.attr="disabled"
            wire:loading.class="opacity-75 cursor-wait"
            class="inline-flex items-center justify-center px-10 py-3 text-sm font-medium text-center text-white transition-all duration-500 border rounded-full shadow-sm border-primary bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="addToCart">+ Keranjang</span>
            <span wire:loading wire:target="addToCart" class="flex items-center gap-2">
                <svg class="w-4 h-4 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>Menambahkan...</span>
            </span>
        </button>

        @if($selectedListingId)
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full border border-gray-300 bg-white/80 hover:border-red-500 hover:bg-red-50 transition-colors">
                <livewire:ecommerce.add-to-wishlist :vendorListingId="$selectedListingId" :key="'wishlist-'.$selectedListingId" />
            </div>
        @endif

    </div>
</div>

@script
<script>
    $wire.on('cart-success', (event) => {
        // Show success notification
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        console.log(event.message || 'Produk berhasil ditambahkan ke keranjang!');
    });

    $wire.on('cart-error', (event) => {
        alert(event.message || 'Terjadi kesalahan.');
    });

    $wire.on('wishlist-success', (event) => {
        console.log(event.message || 'Produk ditambahkan ke wishlist!');
    });

    $wire.on('wishlist-error', (event) => {
        alert(event.message || 'Terjadi kesalahan dengan wishlist.');
    });
</script>
@endscript
