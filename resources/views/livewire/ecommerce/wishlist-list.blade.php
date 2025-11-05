<section class="lg:py-10 py-6">
    <!-- Notification Scripts -->
    @script
    <script>
        $wire.on('cart-success', (event) => {
            try {
                console.log(event.message || 'Berhasil ditambahkan ke keranjang!');
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

        $wire.on('wishlist-success', (event) => {
            try {
                console.log(event.message || 'Berhasil!');
            } catch (e) {
                console.error('Notification error:', e);
            }
        });

        $wire.on('wishlist-error', (event) => {
            try {
                alert(event.message || 'Terjadi kesalahan!');
            } catch (e) {
                console.error('Notification error:', e);
            }
        });
    </script>
    @endscript

    <div class="container">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Wishlist Saya</h1>
            <p class="text-sm text-gray-500 mt-1">Produk yang Anda simpan untuk nanti</p>
        </div>

        @if(!$wishlist || $this->wishlistItems->isEmpty())
            <!-- Empty State -->
            <div class="border rounded-lg border-gray-200 p-12 text-center bg-white">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">Wishlist Anda Kosong</h3>
                <p class="mt-2 text-sm text-gray-500">Simpan produk favorit Anda di sini untuk nanti</p>
                <div class="mt-6">
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-primary-600 rounded-full hover:bg-primary-700">
                        Mulai Belanja
                    </a>
                </div>
            </div>
        @else
            <div class="border border-gray-200 divide-y divide-gray-200 rounded-lg overflow-hidden bg-white">
                @foreach($this->wishlistItems as $item)
                    @php
                        $listing = $item->vendor_listing;
                        $variant = $listing->product_variant ?? null;
                        $product = $variant->product ?? null;
                        $brand = $product->brand ?? null;
                        $shop = $listing->shop ?? null;
                        $media = $product?->media->first();
                    @endphp

                    <div class="px-4 py-4 flex flex-wrap justify-between items-center hover:bg-gray-50 transition-colors">
                        <!-- Product Info -->
                        <div class="md:w-1/2 w-auto">
                            <div class="flex items-center">
                                <!-- Product Image -->
                                <div class="shrink-0 lg:h-28 lg:w-28 w-20 h-20 lg:me-4 me-2 bg-gray-100 rounded-lg overflow-hidden">
                                    @if($media)
                                        <img
                                            src="{{ $media->url }}"
                                            alt="{{ $product->name }}"
                                            class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Details -->
                                <div class="md:w-auto w-2/3">
                                    @if($brand)
                                        <p class="text-xs font-medium text-primary-600 mb-2">{{ $brand->name }}</p>
                                    @endif
                                    <h4 class="text-base lg:text-xl font-semibold text-gray-900 mb-2 line-clamp-2">
                                        {{ $product->name ?? 'Produk' }}
                                    </h4>

                                    <!-- Shop Name -->
                                    @if($shop)
                                        <p class="text-xs text-gray-500 mb-1">
                                            <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            {{ $shop->vendor->store_name ?? 'Toko' }}
                                        </p>
                                    @endif

                                    <!-- Stock Info -->
                                    <div class="flex items-center gap-2 mt-2">
                                        @if($listing->qty_available > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Stok: {{ $listing->qty_available }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                Stok Habis
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="md:w-1/4 w-auto mt-4 md:mt-0">
                            <div class="text-left md:text-right">
                                <h4 class="text-xl lg:text-2xl font-bold text-gray-900">
                                    Rp {{ number_format($listing->price, 0, ',', '.') }}
                                </h4>
                                @if($listing->compare_at_price && $listing->compare_at_price > $listing->price)
                                    <h4 class="text-sm lg:text-base font-medium text-gray-400 line-through">
                                        Rp {{ number_format($listing->compare_at_price, 0, ',', '.') }}
                                    </h4>
                                    @php
                                        $discount = round((($listing->compare_at_price - $listing->price) / $listing->compare_at_price) * 100);
                                    @endphp
                                    <span class="inline-block mt-1 text-xs font-semibold text-red-600">
                                        Hemat {{ $discount }}%
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="md:w-auto w-full lg:mt-0 mt-4">
                            <div class="flex lg:flex-col justify-between gap-2">
                                @if($listing->qty_available > 0)
                                    <button
                                        wire:click="addToCart({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $item->id }})"
                                        class="py-3 px-6 font-medium text-center text-white bg-primary-600 rounded-full hover:bg-primary-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="addToCart({{ $item->id }})">
                                            Tambah ke Keranjang
                                        </span>
                                        <span wire:loading wire:target="addToCart({{ $item->id }})">
                                            <svg class="animate-spin h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                @else
                                    <button disabled class="py-3 px-6 font-medium text-center text-gray-400 bg-gray-200 rounded-full cursor-not-allowed">
                                        Stok Habis
                                    </button>
                                @endif

                                <button
                                    wire:click="removeFromWishlist({{ $item->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="removeFromWishlist({{ $item->id }})"
                                    wire:confirm="Apakah Anda yakin ingin menghapus produk ini dari wishlist?"
                                    class="py-3 px-6 font-medium text-center lg:text-red-600 rounded-full lg:hover:bg-red-50 lg:bg-transparent bg-red-600 text-white lg:border lg:border-red-600 transition-all disabled:opacity-50">
                                    <span wire:loading.remove wire:target="removeFromWishlist({{ $item->id }})">
                                        Hapus
                                    </span>
                                    <span wire:loading wire:target="removeFromWishlist({{ $item->id }})">
                                        <svg class="animate-spin h-5 w-5 inline-block" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center">
                    <p class="text-gray-700">
                        <span class="font-semibold">{{ $this->wishlistItems->count() }}</span> produk di wishlist Anda
                    </p>
                    <a href="/" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                        Lanjut Belanja →
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
