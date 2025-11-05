<button
    wire:click="toggleWishlist"
    wire:loading.attr="disabled"
    type="button"
    class="inline-flex items-center justify-center transition-all duration-200 disabled:opacity-50"
    title="{{ $isInWishlist ? 'Hapus dari wishlist' : 'Tambah ke wishlist' }}">

    @if($isInWishlist)
        <!-- Filled Heart (In Wishlist) -->
        <svg
            wire:loading.remove
            class="w-6 h-6 text-red-500 fill-red-500 hover:scale-110 transition-transform"
            fill="currentColor"
            viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
        </svg>
    @else
        <!-- Outline Heart (Not In Wishlist) -->
        <svg
            wire:loading.remove
            class="w-6 h-6 text-gray-600 hover:text-red-500 hover:scale-110 transition-all"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
        </svg>
    @endif

    <!-- Loading Spinner -->
    <svg
        wire:loading
        class="animate-spin h-6 w-6 text-gray-400"
        fill="none"
        viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
</button>
