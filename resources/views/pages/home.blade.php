@extends('layouts.app')

@section('content')
    @php
        // helper format rupiah (dipakai di kartu rekomendasi)
        $idr = fn(int|float $n) => 'Rp. ' . number_format($n, 0, ',', '.');
    @endphp

    {{-- =========================
    HERO SECTION (OGiTU)
    ========================= --}}
    <section class="relative py-6 lg:py-16">
        <div class="absolute inset-0 blur-[60px] bg-linear-to-l from-purple-600/20 via-purple-600/5 to-purple-600/0"></div>
        <div class="container relative">
            <div class="grid items-center lg:grid-cols-2">
                <div class="px-10 py-20">
                    <div class="z-10 flex items-center justify-center order-last lg:justify-start lg:order-first">
                        <div class="text-center lg:text-start">
                            @foreach ($hero['badges'] ?? [] as $badge)
                                <span
                                    class="inline-flex px-4 py-2 mb-8 text-sm rounded-full text-primary bg-primary/20 lg:mb-2">{{ $badge['label'] }}</span>
                            @endforeach

                            <h1
                                class="mb-5 text-3xl font-bold capitalize lg:text-3xl/normal md:text-5xl/snug text-default-950">
                                {{ $hero['title']['prefix'] ?? '' }}
                                <span class="text-primary">{{ $hero['title']['highlight'] ?? '' }}</span>
                                {{ $hero['title']['suffix'] ?? '' }}
                            </h1>

                            <p class="mx-auto mb-8 text-lg font-medium text-default-700 md:max-w-md lg:mx-0">
                                {{ $hero['desc'] ?? '' }}
                            </p>

                            <div class="flex flex-wrap items-center justify-center gap-5 mt-10 lg:justify-normal">
                                @foreach (($hero['buttons'] ?? []) as $btn)
                                    @if (($btn['type'] ?? '') === 'primary')
                                        <a href="{{ $btn['url'] ?? 'javascript:void(0)' }}"
                                            class="px-10 py-5 font-medium text-white transition-all rounded-full bg-primary hover:bg-primary-500">
                                            {{ $btn['label'] ?? '' }}
                                        </a>
                                    @else
                                        <a href="{{ $btn['url'] ?? 'javascript:void(0)' }}" class="flex items-center text-primary">
                                            <span
                                                class="flex items-center justify-center border-2 rounded-full border-primary-400 h-14 w-14 border-e-transparent me-2">
                                                <i data-lucide="play" class="w-6 h-6 fill-primary"></i>
                                            </span>
                                            <span class="font-semibold">{{ $btn['label'] ?? '' }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Social proof --}}
                            <div class="mt-14">
                                <div class="flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                                    <div class="flex items-center -space-x-1">
                                        @foreach (($hero['avatars'] ?? []) as $a)
                                            <div class="w-12 h-12">
                                                <img class="object-cover object-center w-full h-full rounded-full ring ring-default-50"
                                                    src="{{ $a }}" alt="avatar">
                                            </div>
                                        @endforeach
                                    </div>
                                    <div>
                                        <h1 class="text-base font-medium text-default-800">Kepuasan pelanggan</h1>
                                        <p class="text-base text-default-900">
                                            <i data-lucide="star"
                                                class="inline w-4 h-4 text-purple-400 fill-purple-400"></i>
                                            {{ number_format($hero['rating']['score'] ?? 4.8, 1) }}
                                            <span
                                                class="text-sm text-default-500">({{ $hero['rating']['count_label'] ?? '0 Ulasan' }})</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Notice usia/nicotine --}}
                            <p class="mt-6 text-xs text-default-500">{{ $hero['notice'] ?? '' }}</p>
                        </div>
                    </div>
                </div><!-- end grid-col -->

                {{-- Visual kanan --}}
                <div class="relative flex items-center justify-center py-20">
                    <span class="absolute top-0 start-0 text-3xl -rotate-40">🔥</span>
                    <span
                        class="absolute top-0 end-[10%] -rotate-12 h-14 w-14 inline-flex items-center justify-center bg-purple-400 text-white rounded-lg">
                        <i data-lucide="clock-3" class="w-6 h-6"></i>
                    </span>
                    <span
                        class="absolute inline-flex items-center justify-center w-4 h-4 text-white rounded top-1/4 end-0 -rotate-12 bg-primary"></span>

                    {{-- Review card --}}
                    <div class="absolute hidden bottom-1/4 end-0 2xl:-end-24 md:block lg:hidden xl:block">
                        <img src="{{ $hero['review_card']['image'] ?? '' }}" alt="" class="w-auto h-auto">
                        <div class="flex items-center gap-2 p-2 rounded-full shadow-lg pe-6 bg-default-50">
                            <img src="{{ $hero['review_card']['avatar'] ?? asset('assets/images/avatar1-25906796.png') }}"
                                class="w-16 h-16 rounded-full" alt="review avatar">
                            <div>
                                <h6 class="text-sm font-medium text-default-900">
                                    {{ $hero['review_card']['name'] ?? 'Pelanggan' }}
                                </h6>
                                <p class="text-[10px] font-medium text-default-900">{{ $hero['review_card']['text'] ?? '' }}
                                </p>
                                <span class="inline-flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i data-lucide="star"
                                            class="w-3 h-3 {{ $i <= ($hero['review_card']['score'] ?? 5) ? 'text-purple-400 fill-purple-400' : 'text-default-200 fill-default-200' }}"></i>
                                    @endfor
                                </span>
                            </div>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-0 inline-flex items-center justify-center w-4 h-4 text-white rounded-full end-0 -rotate-12 bg-primary"></span>
                    <span class="absolute text-3xl -bottom-16 end-1/3">🔥</span>

                    {{-- Chip kiri bawah --}}
                    <div class="absolute bottom-0 start-0">
                        <div class="flex items-center gap-2 p-2 rounded-full shadow-lg pe-6 bg-default-50">
                            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/20">
                                <img src="{{ $hero['chip']['icon'] ?? $hero['brand_icon'] ?? '' }}"
                                    class="w-10 h-10 rounded-full" alt="chip icon">
                            </span>
                            <div>
                                <h6 class="text-sm font-medium text-default-900">{{ $hero['chip']['title'] ?? '' }}</h6>
                                <span class="inline-flex gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i data-lucide="star"
                                            class="w-3 h-3 {{ $i <= ($hero['chip']['score'] ?? 5) ? 'text-purple-400 fill-purple-400' : 'text-default-200 fill-default-200' }}"></i>
                                    @endfor
                                </span>
                                <h6 class="text-sm font-medium text-default-900">
                                    <span class="text-sm text-primary">{{ $hero['chip']['currency'] ?? 'Rp' }}</span>
                                    {{ $idr($hero['chip']['price'] ?? 0) }}
                                </h6>
                            </div>
                        </div>
                    </div>

                    <img src="{{ $hero['hero_img'] ?? '' }}" class="mx-auto" alt="hero">
                </div><!-- end grid-col -->
            </div><!-- end grid -->
        </div><!-- end container -->
    </section>

    {{-- =========================
    TENTANG OGiTU
    ========================= --}}
    <section class="py-6 lg:py-16">
        <div class="container">
            <div class="grid items-center gap-10 lg:grid-cols-2 lg:items-start">
                <div class="flex items-center justify-center w-full mx-auto rounded-lg lg:w-10/12 bg-default-500/5">
                    <img src="{{ $about['image'] ?? '' }}" class="w-1/5 sm:w-1/6 md:w-1/4 lg:w-1/3" alt="about image">
                </div>

                <div class="text-center lg:text-left">
                    <span
                        class="inline-flex px-4 py-2 mb-6 text-sm rounded-full text-primary bg-primary/20">{{ $about['badge'] ?? 'Tentang' }}</span>
                    <h2 class="max-w-xl mx-auto mb-6 text-3xl font-semibold lg:mx-0 text-default-900">
                        {{ $about['title'] ?? '' }}
                    </h2>
                    <p class="max-w-2xl mx-auto mb-16 font-medium lg:mx-0 text-default-500 xl:mb-20">
                        {{ $about['desc'] ?? '' }}
                    </p>

                    <div class="grid gap-6 xl:grid-cols-3 sm:grid-cols-2">
                        @foreach (($about['features'] ?? []) as $f)
                            <div
                                class="transition-all duration-200 bg-transparent border rounded-md shadow-lg border-default-100 hover:border-primary">
                                <div class="p-6">
                                    <div class="mb-6">
                                        <img src="{{ $f['icon'] ?? '' }}" class="w-12 h-12 mx-auto" alt="feature icon">
                                    </div>
                                    <h3 class="mb-6 text-xl font-medium text-default-900">{{ $f['title'] ?? '' }}</h3>
                                    <p class="text-base text-default-500">{{ $f['desc'] ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-4 mt-10 lg:justify-start">
                        <a href="{{ $about['cta']['url'] ?? 'javascript:void(0)' }}"
                            class="px-10 py-3 font-medium text-white transition-all rounded-full bg-primary hover:bg-primary-500">
                            {{ $about['cta']['label'] ?? 'Jelajahi' }}
                        </a>
                        <div class="flex items-center gap-2">
                            <img src="{{ $about['ceo']['avatar'] ?? asset('assets/images/avatar3-2bbdc0fd.png') }}"
                                class="w-12 h-12 rounded-full" alt="ceo avatar">
                            <div class="text-center lg:text-left">
                                <h6 class="text-base font-medium text-default-900">{{ $about['ceo']['name'] ?? '' }}</h6>
                                <p class="text-sm font-medium text-default-500">{{ $about['ceo']['role'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =========================
    REKOMENDASI PRODUK
    ========================= --}}
    <section class="py-6 lg:py-10">
        <div class="container">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-10">
                <h3 class="text-xl font-semibold text-default-950">Rekomendasi untukmu.</h3>
                <a href="{{ url('products') }}"
                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:text-primary-500">
                    Lihat semua <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-5 xl:grid-cols-6 md:grid-cols-3">
                @foreach ($recommended as $p)
                    <div
                        class="order-3 p-4 overflow-hidden transition-all duration-300 border rounded-lg border-default-200 hover:border-primary hover:shadow-xl">
                        <div class="relative overflow-hidden divide-y rounded-lg divide-default-200 group">
                            <div class="flex justify-center mx-auto mb-4">
                                <img class="w-full h-full transition-all group-hover:scale-105" src="{{ $p['image'] }}"
                                    alt="{{ $p['title'] }}">
                            </div>
                            <div class="pt-2">
                                <div class="items-center mb-2">
                                    <a class="relative text-xl font-semibold text-default-800 line-clamp-1 after:absolute after:inset-0"
                                        href="{{ $p['url'] }}">{{ $p['title'] }}</a>
                                </div>

                                {{-- Nama store (fallback: "official") --}}
                                <div class="mb-3">
                                    <span
                                        class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-default-100 text-default-700">
                                        {{ data_get($p, 'shop_name', 'official') }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-2 mb-4">
                                    <div class="flex items-center gap-1">
                                        <span class="flex items-center justify-center p-1 rounded-full bg-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                class="w-3 h-3 text-white" fill="currentColor">
                                                <polygon
                                                    points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        </span>
                                        <span class="text-sm text-default-950">{{ number_format($p['rating'], 1) }}</span>
                                    </div>
                                    <h4 class="text-sm font-semibold text-default-900">{{ $idr($p['price']) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================
    UNDUH APLIKASI / PROMO
    ========================= --}}
    <section class="py-6 lg:py-16">
        <div class="container">
            <div class="rounded-lg bg-primary/10">
                <div class="grid items-center gap-6 lg:grid-cols-2">
                    <div class="relative h-full p-6 lg:p-20">
                        <span class="absolute text-xl rotate-45 end-16 top-1/3">😃</span>
                        <span class="absolute text-xl rotate-45 end-0 top-1/2">🔥</span>
                        <span
                            class="absolute inline-flex items-center justify-center w-2 h-2 text-white rounded-full bottom-40 end-40 bg-primary"></span>

                        <div class="absolute hidden sm:block -bottom-10 lg:bottom-10 lg:end-0 end-10">
                            <div class="p-2 rounded-full shadow-lg bg-default-50">
                                <div class="flex items-center gap-4">
                                    <div class="overflow-hidden rounded-full h-14 w-14">
                                        <img src="{{ asset('assets/images/avatar4-85475652.png') }}" alt="courier">
                                    </div>
                                    <div>
                                        <h6 class="mb-1 text-base font-medium text-default-900">Tim Support Ogitu</h6>
                                        <p class="text-sm font-medium text-default-500">Siap bantu order Anda</p>
                                    </div>
                                    <div
                                        class="inline-flex items-center justify-center text-white rounded-full h-14 w-14 bg-primary">
                                        <i data-lucide="phone"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <span class="inline-flex px-4 py-2 mb-6 text-sm rounded-full text-primary bg-primary/20">Unduh
                            Aplikasi</span>
                        <h2 class="max-w-sm mb-6 font-semibold text-3xl/relaxed text-default-900">Belanja Lebih Praktis di
                            Ogitu!</h2>
                        <p class="max-w-md mb-10 text-base text-default-900">Notifikasi promo terbaru, cek status pesanan,
                            dan riwayat transaksi—semua dalam satu tempat.</p>
                        <a href="javascript:void(0)"
                            class="inline-flex px-10 py-4 font-medium text-white transition-all rounded-full bg-primary hover:bg-primary-500">Unduh
                            sekarang</a>
                    </div>

                    <div class="relative px-20 pt-20">
                        <span class="absolute text-3xl -rotate-45 end-10 bottom-28">🔥</span>
                        <span
                            class="absolute inline-flex items-center justify-center w-3 h-3 text-white rounded-full bottom-10 end-20 bg-primary"></span>
                        <span
                            class="absolute top-1/4 end-10 h-2.5 w-2.5 inline-flex items-center justify-center bg-purple-400 text-white rounded-full"></span>
                        <span class="absolute text-xl -rotate-45 end-1/4 top-12">😋</span>
                        <span
                            class="absolute inline-flex items-center justify-center w-2 h-2 text-white rounded-full start-10 top-12 bg-primary"></span>
                        <img src="https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png" class="max-w-full max-h-full"
                            alt="app mockup">
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
