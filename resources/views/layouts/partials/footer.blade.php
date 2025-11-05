<footer class="border-t border-default-200 dark:border-gray-700">
    <div class="container">
        @php
            // Data dari View Composer (footerNav dan legal sudah di-inject)
            // Data statis lainnya
            $kontak = [
                'judul' => 'Hubungi Kami',
                'telepon' => config('app.phone', '(+62) 812-3456-7890'),
                'email' => config('app.email', 'halo@ogitu.com'),
            ];

            $sosial = [
                ['ikon' => 'phone', 'url' => 'tel:'.preg_replace('/[^0-9\+]/', '', $kontak['telepon'])],
                ['ikon' => 'globe', 'url' => url('/')],
                ['ikon' => 'instagram', 'url' => config('app.social.instagram', '#')],
                ['ikon' => 'twitter', 'url' => config('app.social.twitter', '#')],
                ['ikon' => 'youtube', 'url' => config('app.social.youtube', '#')],
                ['ikon' => 'facebook', 'url' => config('app.social.facebook', '#')],
            ];

            $newsletter = [
                'judul' => 'Berlangganan',
                'placeholder' => 'Alamat email',
                'deskripsi' => 'Dapatkan kabar terbaru, penawaran, dan artikel pilihan langsung ke inbox Anda.',
            ];
        @endphp

        <div class="grid items-center gap-6 py-6 lg:grid-cols-3 lg:py-10">
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 gap-6 mb-6 md:grid-cols-4">
                    {{-- Kolom menu (3 kolom) - Data dari WebPages dengan layout='footer' --}}
                    @foreach ($footerNav as $grup)
                        <div class="flex flex-col gap-3">
                            <h5 class="mb-3 font-semibold text-default-950 dark:text-white">{{ $grup['judul'] }}</h5>
                            @if(!empty($grup['tautan']))
                                @foreach ($grup['tautan'] as $link)
                                    <div class="text-default-600 dark:text-gray-400">
                                        <a href="{{ $link['url'] }}" class="hover:text-primary dark:hover:text-primary-400 transition-colors">{{ $link['teks'] }}</a>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-default-400 dark:text-gray-500 text-sm">
                                    Belum ada halaman
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Kolom kontak & sosial --}}
                    <div class="flex flex-col gap-3">
                        <h5 class="mb-3 font-semibold text-default-950 dark:text-white">{{ $kontak['judul'] }}</h5>
                        <div class="text-default-600 dark:text-gray-400">
                            <a href="tel:{{ preg_replace('/[^0-9\+]/', '', $kontak['telepon']) }}" class="hover:text-primary dark:hover:text-primary-400">{{ $kontak['telepon'] }}</a>
                        </div>
                        <div class="text-default-600 dark:text-gray-400">
                            <a href="mailto:{{ $kontak['email'] }}" class="hover:text-primary dark:hover:text-primary-400">{{ $kontak['email'] }}</a>
                        </div>
                        <div class="flex items-center gap-4">
                            @foreach ($sosial as $s)
                                <a href="{{ $s['url'] }}" class="cursor-pointer">
                                    <i data-lucide="{{ $s['ikon'] }}" class="w-6 h-6 transition-all text-default-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary-400"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom newsletter --}}
            <div class="col-span-1">
                <div class="flex flex-col gap-3">
                    <div class="rounded-lg bg-primary/10 dark:bg-primary/20">
                        <div class="p-8">
                            @livewire('ecommerce.newsletter')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar bawah --}}
        <div class="hidden py-6 border-t border-default-200 dark:border-gray-700 lg:flex">
            <div class="container">
                <div class="grid items-center gap-6 lg:grid-cols-2">
                    <div>
                        <p class="text-default-600 dark:text-gray-400">
                            {{ now()->year }} · Desain dibuat dengan
                            <i data-lucide="heart" class="inline w-4 h-4 text-red-500 fill-red-500"></i>
                            oleh Sas
                        </p>
                    </div>

                    <div class="flex justify-end gap-6">
                        {{-- Legal links dari WebPages dengan schema_type: syarat, privasi, cookie --}}
                        @if(!empty($legal))
                            @foreach ($legal as $l)
                                <a href="{{ $l['url'] }}" class="font-medium text-default-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary-400 transition-colors">
                                    {{ $l['teks'] }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

{{-- Tombol Back to Top & Toggle Tema --}}
<div class="fixed lg:bottom-5 end-5 bottom-18 flex flex-col items-center bg-primary/25 rounded-full z-10">
    <button class="h-0 w-8 opacity-0 flex justify-center items-center transition-all duration-500 translate-y-5 z-10" data-toggle="back-to-top" aria-label="Kembali ke atas">
        <i class="h-5 w-5 text-primary-500 mt-1" data-lucide="chevron-up"></i>
    </button>
    <button class="rounded-full h-10 w-10 bg-primary text-white flex justify-center items-center z-20" aria-label="Ganti tema">
        <i class="h-5 w-5" data-lucide="sun" id="light-theme"></i>
        <i class="h-5 w-5" data-lucide="moon" id="dark-theme"></i>
    </button>
</div>
