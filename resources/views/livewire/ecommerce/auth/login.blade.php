<div class="relative flex items-center md:h-screen sm:py-16 py-36 bg-linear-to-b from-primary/5 via-primary/5 to-primary/10">
    <!-- Success Notification -->
    @script
    <script>
        $wire.on('login-success', (event) => {
            try {
                alert(event.message || 'Login berhasil!');
            } catch (e) {
                console.error('Notification error:', e);
            }
        });
    </script>
    @endscript

    <div class="container">
        <div class="flex items-center justify-center lg:max-w-lg">
            <div class="flex flex-col h-full">
                <div class="shrink">
                    <div>
                        <a href="/" class="flex items-center">
                            <img src="https://sinergiabadisentosa.com/wp-content/uploads/2024/08/cropped-Pt-sinergi-abadi-sentosa-png-2.png"
                                alt="logo" class="flex h-12 dark:hidden">
                            <img src="https://sinergiabadisentosa.com/wp-content/uploads/2024/08/cropped-Pt-sinergi-abadi-sentosa-png-2.png"
                                alt="logo" class="hidden h-12 dark:flex">
                        </a>
                    </div>
                    <div class="py-10">
                        <h1 class="mb-2 text-3xl font-semibold text-default-800">Login</h1>
                        <p class="max-w-md text-sm text-default-500">Masuk ke akun Anda untuk melanjutkan berbelanja dan mengakses fitur eksklusif.</p>
                    </div>

                    <form wire:submit="login">
                        <!-- Email Field -->
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-default-900" for="email">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="email"
                                id="email"
                                type="email"
                                class="block w-full rounded-full py-2.5 px-4 bg-white border border-default-200 focus:ring-primary focus:border-primary dark:bg-default-50 @error('email') border-red-500 @enderror"
                                placeholder="Masukkan email Anda"
                                autofocus
                            >
                            @error('email')
                                <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-default-900" for="password">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <a href="/forgot-password" class="text-xs text-default-700 hover:text-primary">Lupa Password?</a>
                            </div>
                            <div class="flex" x-data="{ showPassword: false }">
                                <input
                                    wire:model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    class="block w-full rounded-s-full py-2.5 px-4 bg-white border border-default-200 focus:ring-primary focus:border-primary dark:bg-default-50 @error('password') border-red-500 @enderror"
                                    placeholder="Masukkan password Anda"
                                >
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="inline-flex items-center justify-center py-2.5 px-4 border rounded-e-full bg-white -ms-px border-default-200 dark:bg-default-50 hover:bg-default-50"
                                >
                                    <i x-show="!showPassword" class="w-5 h-5 text-default-600" data-lucide="eye"></i>
                                    <i x-show="showPassword" class="w-5 h-5 text-default-600" data-lucide="eye-off"></i>
                                </button>
                            </div>
                            @error('password')
                                <span class="text-sm text-red-500 mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center mb-6">
                            <input
                                wire:model="remember"
                                id="remember"
                                type="checkbox"
                                class="w-4 h-4 text-primary bg-white border-default-300 rounded focus:ring-primary focus:ring-2"
                            >
                            <label for="remember" class="ms-2 text-sm font-medium text-default-900">
                                Ingat saya
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-center mb-6">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="relative inline-flex items-center justify-center w-full px-6 py-3 text-base text-white capitalize transition-all rounded-full bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="login">Masuk</span>
                                <span wire:loading wire:target="login" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Register Link -->
                <div class="flex items-end justify-center mt-16 grow">
                    <p class="mt-auto text-center text-default-950">
                        Belum punya akun?
                        <a href="/register" class="text-primary ms-1 hover:underline">
                            <span class="font-medium">Daftar Sekarang</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Decorations -->
    <div>
        <div class="absolute top-0 hidden end-0 xl:flex h-5/6">
            <img src="https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png"
                class="z-0 w-full h-3/4" alt="decoration">
        </div>
    </div>
</div>
