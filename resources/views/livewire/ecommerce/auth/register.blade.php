<div class="relative min-h-screen flex items-center py-12 md:py-16 bg-linear-to-br from-primary-50 via-white to-primary-50">
    {{-- Background Decoration --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-200 rounded-full mix-blend-multiply blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-primary-300 rounded-full mix-blend-multiply blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-primary-100 rounded-full mix-blend-multiply blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
    </div>

    <div class="container relative z-10">
        <div class="mx-auto max-w-6xl">
            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-xl border border-default-200 overflow-hidden">
                {{-- Header --}}
                <div class="bg-linear-to-r from-primary-600 to-primary-700 px-6 md:px-8 py-6">
                    <div class="flex items-start md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">Daftar Akun</h1>
                            <p class="text-primary-100/90 text-sm md:text-base mt-1">
                                Bergabunglah dengan Ogitu dan dapatkan keuntungan dari program referral
                            </p>
                        </div>
                        <div class="hidden sm:flex">
                            <div class="w-14 h-14 md:w-16 md:h-16 bg-white/15 rounded-full flex items-center justify-center backdrop-blur-sm ring-1 ring-white/20">
                                {{-- user-plus --}}
                                <svg class="w-7 h-7 md:w-8 md:h-8 text-white" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="10" cy="8" r="3.5"></circle>
                                    <path d="M3 19a8 5 0 0 1 14 0"></path>
                                    <path d="M19 5v4M17 7h4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-5 gap-6 md:gap-8">
                    {{-- Left: Form --}}
                    <div class="md:col-span-3">
                        {{-- Success --}}
                        @if (session()->has('success'))
                            <div role="alert" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    {{-- check-circle --}}
                                    <svg class="w-5 h-5 text-green-600 mt-0.5" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M8.5 12.5l2.5 2.5 4.5-5"></path>
                                    </svg>
                                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Error --}}
                        @if ($errors->has('register'))
                            <div role="alert" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    {{-- alert-circle --}}
                                    <svg class="w-5 h-5 text-red-600 mt-0.5" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"></circle>
                                        <path d="M12 8v5M12 17h.01"></path>
                                    </svg>
                                    <p class="text-sm text-red-800">{{ $errors->first('register') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Referral highlight --}}
                        @if($sponsor || $referral_code)
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-start gap-3">
                                    {{-- gift --}}
                                    <svg class="w-5 h-5 text-green-600 mt-0.5" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M20 12v8H4v-8h16Z"></path>
                                        <path d="M12 12v8M4 16h16"></path>
                                        <path d="M12 12s-3-1.2-3-3a2 2 0 1 1 3 3Zm0 0s3-1.2 3-3a2 2 0 1 0-3 3Z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        @if($sponsor)
                                            <p class="text-sm font-medium text-green-900">Direferensikan oleh:</p>
                                            <p class="text-sm text-green-700 mt-1">{{ $sponsor->customer->name }}</p>
                                            <p class="text-xs text-green-600 mt-0.5">Anda akan terhubung dalam jaringan referral</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- FORM --}}
                        <form wire:submit.prevent="register" class="space-y-6">
                            {{-- Personal --}}
                            <div class="space-y-4">
                                <h3 class="text-base md:text-lg font-semibold text-default-900 flex items-center gap-2">
                                    {{-- user --}}
                                    <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="8" r="3.5"></circle>
                                        <path d="M4 19a8 5 0 0 1 16 0"></path>
                                    </svg>
                                    Informasi Pribadi
                                </h3>

                                {{-- Name --}}
                                <div>
                                    <label for="name" class="block text-sm font-medium text-default-900 mb-2">
                                        Nama Lengkap <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                            {{-- user --}}
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="8" r="3.5"></circle>
                                                <path d="M4 19a8 5 0 0 1 16 0"></path>
                                            </svg>
                                        </span>
                                        <input
                                            type="text"
                                            id="name"
                                            wire:model.blur="name"
                                            class="block w-full ps-12 pe-4 py-3 rounded-lg border @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors"
                                            placeholder="Masukkan nama lengkap Anda"
                                            required>
                                    </div>
                                    @error('name')
                                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                            {{-- alert-circle --}}
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 8v5M12 17h.01"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Email & Phone --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Email --}}
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-default-900 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                                {{-- mail --}}
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                                    <path d="M3 7l9 6 9-6"></path>
                                                </svg>
                                            </span>
                                            <input
                                                type="email"
                                                id="email"
                                                wire:model.blur="email"
                                                class="block w-full ps-12 pe-4 py-3 rounded-lg border @error('email') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors"
                                                placeholder="email@example.com"
                                                required>
                                        </div>
                                        @error('email')
                                            <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle>
                                                    <path d="M12 8v5M12 17h.01"></path>
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    {{-- Phone --}}
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-default-900 mb-2">
                                            No. Telepon <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                                {{-- smartphone --}}
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="7" y="2" width="10" height="20" rx="2"></rect>
                                                    <path d="M11 18h2"></path>
                                                </svg>
                                            </span>
                                            <input
                                                type="tel"
                                                id="phone"
                                                wire:model.blur="phone"
                                                class="block w-full ps-12 pe-4 py-3 rounded-lg border @error('phone') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors"
                                                placeholder="08123456789"
                                                required>
                                        </div>
                                        @error('phone')
                                            <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle>
                                                    <path d="M12 8v5M12 17h.01"></path>
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- DOB --}}
                                <div>
                                    <label for="dob" class="block text-sm font-medium text-default-900 mb-2">
                                        Tanggal Lahir (Opsional)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                            {{-- calendar --}}
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="17" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                        </span>
                                        <input
                                            type="date"
                                            id="dob"
                                            wire:model.blur="dob"
                                            class="block w-full ps-12 pe-4 py-3 rounded-lg border @error('dob') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors">
                                    </div>
                                    @error('dob')
                                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 8v5M12 17h.01"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Security --}}
                            <div class="space-y-4 pt-6 border-t border-default-200">
                                <h3 class="text-base md:text-lg font-semibold text-default-900 flex items-center gap-2">
                                    {{-- lock --}}
                                    <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Keamanan Akun
                                </h3>

                                {{-- Password --}}
                                <div>
                                    <label for="password" class="block text-sm font-medium text-default-900 mb-2">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                            {{-- lock --}}
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        </span>
                                        <input
                                            type="password"
                                            id="password"
                                            wire:model.blur="password"
                                            class="block w-full ps-12 pe-4 py-3 rounded-lg border @error('password') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors"
                                            placeholder="Minimal 8 karakter"
                                            required>
                                    </div>
                                    @error('password')
                                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 8v5M12 17h.01"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Confirm --}}
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-default-900 mb-2">
                                        Konfirmasi Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                            {{-- lock --}}
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        </span>
                                        <input
                                            type="password"
                                            id="password_confirmation"
                                            wire:model.blur="password_confirmation"
                                            class="block w-full ps-12 pe-4 py-3 rounded-lg border border-default-200 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                            placeholder="Ulangi password Anda"
                                            required>
                                    </div>
                                </div>
                            </div>

                            {{-- Referral --}}
                            <div class="space-y-4 pt-6 border-t border-default-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base md:text-lg font-semibold text-default-900 flex items-center gap-2">
                                        {{-- gift --}}
                                        <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 12v8H4v-8h16Z"></path>
                                            <path d="M12 12v8M4 16h16"></path>
                                            <path d="M12 12s-3-1.2-3-3a2 2 0 1 1 3 3Zm0 0s3-1.2 3-3a2 2 0 1 0-3 3Z"></path>
                                        </svg>
                                        Kode Referral
                                    </h3>
                                    <span class="text-xs text-default-500 bg-default-100 px-2 py-1 rounded">Opsional</span>
                                </div>

                                <div>
                                    <label for="referral_code" class="block text-sm font-medium text-default-900 mb-2">
                                        Punya customer code referral?
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 start-0 w-12 flex items-center justify-center text-default-400 pointer-events-none">
                                            {{-- ticket --}}
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 7h18v4a2 2 0 0 1 0 4v4H3v-4a2 2 0 1 1 0-4V7z"></path>
                                            </svg>
                                        </span>
                                        <input
                                            type="text"
                                            id="referral_code"
                                            wire:model.live.debounce.500ms="referral_code"
                                            class="block w-full ps-12 pe-12 py-3 rounded-lg border @error('referral_code') border-red-300 focus:ring-red-500 focus:border-red-500 @else border-default-200 focus:ring-primary-500 focus:border-primary-500 @enderror transition-colors uppercase"
                                            placeholder="Masukkan customer code (contoh: CST-00123)">
                                        @if($sponsor)
                                            <span class="absolute inset-y-0 end-0 w-10 flex items-center justify-center text-green-600">
                                                {{-- check-circle --}}
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle>
                                                    <path d="M8.5 12.5l2.5 2.5 4.5-5"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    @error('referral_code')
                                        <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9"></circle>
                                                <path d="M12 8v5M12 17h.01"></path>
                                            </svg>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                    <p class="mt-1.5 text-xs text-default-500">
                                        Masukkan customer code dari member yang mereferensikan Anda untuk bergabung dengan jaringan MLM
                                    </p>
                                </div>
                            </div>

                            {{-- Terms --}}
                            <div class="pt-6 border-t border-default-200">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        wire:model.live="agreeTerms"
                                        class="mt-1 w-4 h-4 rounded border-default-300 text-primary-600 focus:ring-primary-500 focus:ring-offset-0 transition-colors cursor-pointer">
                                    <span class="text-sm text-default-700 group-hover:text-default-900 transition-colors">
                                        Saya menyetujui <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">Syarat dan Ketentuan</a> serta <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">Kebijakan Privasi</a>
                                        <span class="text-red-500">*</span>
                                    </span>
                                </label>
                                @error('agreeTerms')
                                    <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1 ml-7">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"></circle>
                                            <path d="M12 8v5M12 17h.01"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="pt-4">
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    @disabled(!$agreeTerms)
                                    class="rounded-full h-10 w-full bg-primary text-white flex justify-center items-center z-20">
                                    <span wire:loading.remove>Daftar Sekarang</span>
                                    <span wire:loading class="flex items-center gap-2">
                                        {{-- Spinner --}}
                                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </button>
                                @if(!$agreeTerms)
                                    <p class="mt-2 text-xs text-center text-default-500">
                                        Centang persetujuan di atas untuk melanjutkan
                                    </p>
                                @endif
                            </div>


                            {{-- Footer link (mobile) --}}
                            <div class="text-center pt-4">
                                <p class="text-sm text-default-600">
                                    Sudah punya akun?
                                    <a href="{{ route('auth.login') }}" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors">
                                        Masuk di sini
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>

                    {{-- Right: Benefits --}}
                    <aside class="md:col-span-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 items-stretch">
                        <div class="h-full rounded-xl border border-default-200 bg-white/60 backdrop-blur-sm p-5">
                            <div class="flex h-full items-start gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center shrink-0">
                                <!-- shield-check -->
                                <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z"></path>
                                <path d="M9.5 12.5l2 2 3.5-3.5"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-sm font-semibold leading-5 text-default-900">Aman & Terpercaya</h4>
                                <p class="text-xs leading-5 text-default-600">Data Anda dilindungi dengan enkripsi tingkat tinggi</p>
                            </div>
                            </div>
                        </div>

                        <div class="h-full rounded-xl border border-default-200 bg-white/60 backdrop-blur-sm p-5">
                            <div class="flex h-full items-start gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center shrink-0">
                                <!-- users -->
                                <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="8" r="3"></circle>
                                <path d="M2 19a7 4 0 0 1 14 0"></path>
                                <circle cx="17" cy="10" r="2.5"></circle>
                                <path d="M14.5 19a6 3 0 0 1 7 0"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-sm font-semibold leading-5 text-default-900">Program Referral</h4>
                                <p class="text-xs leading-5 text-default-600">Dapatkan komisi dari setiap pembelian downline</p>
                            </div>
                            </div>
                        </div>

                        <div class="h-full rounded-xl border border-default-200 bg-white/60 backdrop-blur-sm p-5">
                            <div class="flex h-full items-start gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center shrink-0">
                                <!-- zap -->
                                <svg class="w-5 h-5 text-primary-600" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="mb-1 text-sm font-semibold leading-5 text-default-900">Proses Cepat</h4>
                                <p class="text-xs leading-5 text-default-600">Registrasi dalam hitungan detik</p>
                            </div>
                            </div>
                        </div>
                        </aside>

                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes blob {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }
</style>
@endpush
