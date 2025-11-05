<div>
    <form wire:submit.prevent="subscribe" class="mb-6 space-y-2">
        <label for="subscribeEmail" class="mb-4 text-lg font-medium text-default-950 dark:text-white">
            Berlangganan
        </label>

        {{-- Success Message --}}
        @if (session()->has('newsletter_success'))
            <div class="p-3 mb-4 text-sm text-green-800 bg-green-100 dark:bg-green-900 dark:text-green-200 rounded-lg" role="alert">
                <span class="font-medium">Berhasil!</span> {{ session('newsletter_success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @error('email')
            <div class="p-3 mb-4 text-sm text-red-800 bg-red-100 dark:bg-red-900 dark:text-red-200 rounded-lg" role="alert">
                <span class="font-medium">Error!</span> {{ $message }}
            </div>
        @enderror

        <div class="flex rounded-md shadow-sm">
            <input
                type="email"
                id="subscribeEmail"
                wire:model="email"
                class="block w-full px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-default-200 dark:border-gray-700 rounded-s-md focus:ring-2 focus:ring-primary focus:border-primary"
                placeholder="Alamat email"
                required />
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex shrink-0 justify-center items-center h-11.5 w-11.5 rounded-e-md border border-transparent font-semibold bg-primary text-white hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all text-sm">
                <span wire:loading.remove>
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </span>
                <span wire:loading>
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </div>
    </form>
    <p class="mb-6 text-sm text-default-500 dark:text-gray-400">
        Dapatkan kabar terbaru, penawaran, dan artikel pilihan langsung ke inbox Anda.
    </p>
</div>
