<div class="w-full">
    <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto"> <!-- Added max-w-6xl and mx-auto for centering -->

        {{-- Notifications --}}
        @script
        <script>
            // Note: Removed alert() for a better user experience.
            // In a real app, these events should trigger a UI toast/notification library.
            $wire.on('profile-success', (event) => {
                console.log('SUCCESS:', event.message || 'Profil berhasil diperbarui!');
            });

            $wire.on('profile-error', (event) => {
                console.error('ERROR:', event.message || 'Terjadi kesalahan saat memperbarui profil.');
            });

            $wire.on('password-success', (event) => {
                console.log('SUCCESS:', event.message || 'Password berhasil diperbarui!');
            });

            $wire.on('password-error', (event) => {
                console.error('ERROR:', event.message || 'Terjadi kesalahan saat mengubah password.');
            });
        </script>
        @endscript

        {{-- Main Content Grid: 1 column on mobile, 2 columns on large screens --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8"> <!-- Diterapkan grid 2 kolom responsif dengan gap 8 -->

            {{-- Personal Details (Kolom 1 pada layar besar) --}}
            <div class="p-6 border rounded-xl border-default-200 bg-white shadow-lg">
                <h4 class="text-2xl font-semibold text-default-900 mb-6 border-b pb-3">Detail Pribadi</h4>

                <form wire:submit="updateProfile">
                    {{-- Responsive Grid: 2 columns on medium screens and up --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- Name (Full Width on MD+) --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-default-900 mb-2" for="name">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="name"
                                id="name"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="text"
                                placeholder="Masukkan nama lengkap Anda">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email (Half Width on MD+) --}}
                        <div>
                            <label class="block text-sm font-medium text-default-900 mb-2" for="email">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="email"
                                id="email"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="email"
                                placeholder="email@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone (Half Width on MD+) --}}
                        <div>
                            <label class="block text-sm font-medium text-default-900 mb-2" for="phone">
                                Nomor Telepon
                            </label>
                            <input
                                wire:model="phone"
                                id="phone"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="tel"
                                placeholder="+62-xxx-xxxx-xxxx">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Date of Birth (Full Width on MD+) --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-default-900 mb-2" for="dob">
                                Tanggal Lahir
                            </label>
                            <input
                                wire:model="dob"
                                id="dob"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="date">
                            @error('dob')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="md:col-span-2 pt-4">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center gap-2 rounded-lg border border-primary bg-primary px-8 py-2.5 text-center text-sm font-semibold text-white shadow-md transition-all duration-200 hover:border-primary-700 hover:bg-primary-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="updateProfile">
                                    Simpan Perubahan
                                </span>
                                <span wire:loading wire:target="updateProfile" class="flex items-center gap-2">
                                    <!-- **FIXED wire:target from updatePassword to updateProfile** -->
                                    <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Change Password (Kolom 2 pada layar besar) --}}
            <div class="p-6 border rounded-xl border-default-200 bg-white shadow-lg">
                <h4 class="text-2xl font-semibold text-default-900 mb-6 border-b pb-3">Ubah Password</h4>

                <form wire:submit="updatePassword">
                    {{-- Simple Grid: 1 column is best for password changes --}}
                    <div class="grid grid-cols-1 gap-6 md:w-full"> <!-- Lebar formulir diubah menjadi w-full agar sejajar dengan kolom sebelah -->

                        {{-- Current Password --}}
                        <div>
                            <label class="block text-sm font-medium text-default-900 mb-2" for="current_password">
                                Password Saat Ini <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="current_password"
                                id="current_password"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="password"
                                placeholder="Masukkan password saat ini">
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- New Password --}}
                        <div>
                            <label class="block text-sm font-medium text-default-900 mb-2" for="new_password">
                                Password Baru <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="new_password"
                                id="new_password"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="password"
                                placeholder="Masukkan password baru">
                            @error('new_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div>
                            <label class="block text-sm font-medium text-default-900 mb-2" for="new_password_confirmation">
                                Konfirmasi Password Baru <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="new_password_confirmation"
                                id="new_password_confirmation"
                                class="block w-full bg-transparent rounded-lg py-2.5 px-4 border border-default-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                type="password"
                                placeholder="Konfirmasi password baru">
                            @error('new_password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-4">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center gap-2 rounded-lg border border-primary bg-primary px-8 py-2.5 text-center text-sm font-semibold text-white shadow-md transition-all duration-200 hover:border-primary-700 hover:bg-primary-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="updatePassword">
                                    Ubah Password
                                </span>
                                <span wire:loading wire:target="updatePassword" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mengubah...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Account Info (Membentang 2 Kolom pada layar besar) --}}
            <div class="p-6 border rounded-xl border-default-200 bg-white shadow-lg lg:col-span-2">
                <h4 class="text-2xl font-semibold text-default-900 mb-6 border-b pb-3">Informasi Akun</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-6">
                    <!-- Item 1 -->
                    <div class="md:col-span-1 border-b md:border-b-0 md:border-r border-default-100 pb-4 md:pb-0 md:pr-6">
                        <span class="block text-sm text-default-600 mb-1">Kode Customer</span>
                        <span class="text-lg font-semibold text-default-900">{{ $customer->customer_code ?? '-' }}</span>
                    </div>

                    <!-- Item 2 -->
                    <div class="md:col-span-1 border-b md:border-b-0 md:border-r border-default-100 pb-4 md:pb-0 md:pr-6">
                        <span class="block text-sm text-default-600 mb-1">Status Akun</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ ($customer->status ?? '') === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($customer->status ?? 'Inactive') }}
                        </span>
                    </div>

                    <!-- Item 3 -->
                    <div class="md:col-span-1">
                        <span class="block text-sm text-default-600 mb-1">Terdaftar Sejak</span>
                        <span class="text-lg font-semibold text-default-900">{{ $customer->created_at->format('d F Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>

        </div> <!-- End of grid -->
    </div>
</div>
