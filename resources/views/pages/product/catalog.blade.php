@extends('layouts.app')

@section('content')
@php
    // helper kecil
    $catalogAction = url('products');
    $sortActive = $activeFilters['sort'] ?? 'newest';
    $sortLabel = [
        'newest'       => 'Terbaru',
        'best_seller'  => 'Terlaris',
        'price_asc'    => 'Harga Termurah',
        'price_desc'   => 'Harga Termahal',
        'rating_desc'  => 'Rating Tertinggi',
    ][$sortActive] ?? 'Terbaru';
@endphp

<section class="py-6 lg:py-8">
    <div class="container">
        <div class="gap-6 lg:flex">

            {{-- =========================
                 SIDEBAR FILTER (WRAP FORM)
            ========================== --}}
            <form id="filterForm" method="GET" action="{{ $catalogAction }}"
                  class="fixed top-0 hidden w-full h-full max-w-xs transition-all transform -translate-x-full bg-white hs-overlay hs-overlay-open:translate-x-0 lg:max-w-full lg:w-1/4 start-0 z-60 lg:z-auto lg:translate-x-0 lg:block lg:static lg:start-auto dark:bg-default-50"
                  tabindex="-1" id="filter_Offcanvas">

                {{-- Keep params lain saat submit --}}
                <input type="hidden" name="search" value="{{ $activeFilters['search'] ?? '' }}">
                <input type="hidden" name="sort" value="{{ $sortActive }}">
                {{-- price ikut dari radio price --}}

                <div class="flex items-center justify-between px-4 py-3 border-b border-default-200 lg:hidden">
                    <h3 class="font-medium text-default-800">Pilihan Filter</h3>
                    <button class="inline-flex items-center justify-center shrink-0 w-8 h-8 text-sm rounded-md text-default-500 hover:text-default-700"
                            data-hs-overlay="#filter_Offcanvas" type="button">
                        <span class="sr-only">Tutup modal</span>
                        <i class="w-5 h-5" data-lucide="x"></i>
                    </button>
                </div>

                <div class="h-[calc(100vh-128px)] overflow-y-auto lg:h-auto" data-simplebar>
                    <div class="p-6 divide-y lg:p-0 divide-default-200">

                        {{-- 1) KATEGORI --}}
                        <div>
                            <button class="inline-flex items-center justify-between w-full gap-2 py-4 text-lg font-medium uppercase transition-all hs-collapse-toggle text-default-900 open"
                                    data-hs-collapse="#all_categories" id="hs-basic-collapse-categories" type="button">
                                Kategori
                            </button>
                            <div class="hs-collapse w-full overflow-hidden transition-[height] duration-300 open" id="all_categories">
                                <div class="relative flex flex-col mb-6 space-y-4">
                                    @foreach ($categories as $cat)
                                        <div class="flex items-center">
                                            @if(($cat['id'] ?? '') === 'all')
                                                <input class="w-5 h-5 bg-transparent rounded-full cursor-pointer form-checkbox text-primary border-default-400 focus:ring-0"
                                                       id="cat_all" type="checkbox"
                                                       @checked($cat['checked'] ?? false)>
                                                <label class="inline-flex items-center text-sm select-none ps-3 text-default-600" for="cat_all">
                                                    {{ $cat['label'] }}
                                                </label>
                                            @else
                                                <input class="w-5 h-5 bg-transparent rounded-full cursor-pointer form-checkbox text-primary border-default-400 focus:ring-0"
                                                       id="cat_{{ $cat['id'] }}" name="category[]"
                                                       value="{{ $cat['id'] }}"
                                                       type="checkbox" @checked($cat['checked'] ?? false)>
                                                <label class="inline-flex items-center text-sm select-none ps-3 text-default-600"
                                                       for="cat_{{ $cat['id'] }}">{{ $cat['label'] }}</label>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- 2) RANGE HARGA --}}
                        <div>
                            <button class="inline-flex items-center justify-between w-full gap-2 py-4 text-lg font-medium uppercase transition-all hs-collapse-toggle text-default-900 open"
                                    data-hs-collapse="#price_range" id="hs-basic-collapse-price" type="button">
                                Range Harga (IDR)
                            </button>
                            <div class="hs-collapse w-full overflow-hidden transition-[height] duration-300 open" id="price_range">
                                <div class="relative flex flex-col mb-6 space-y-5">
                                    {{-- Radio price --}}
                                    <div class="relative flex flex-col mb-6 space-y-4">
                                        @foreach ($priceRanges as $range)
                                            <div class="flex items-center">
                                                <input class="w-5 h-5 bg-transparent rounded-full cursor-pointer form-radio text-primary border-default-400 focus:ring-0"
                                                       id="{{ $range['id'] }}" name="price"
                                                       value="{{ $range['value'] }}"
                                                       type="radio" @checked($range['checked'] ?? false)>
                                                <label class="inline-flex items-center text-sm select-none ps-3 text-default-600"
                                                       for="{{ $range['id'] }}">{{ $range['label'] }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3) BRAND POPULER --}}
                        <div>
                            <button class="inline-flex items-center justify-between w-full gap-2 py-4 text-lg font-medium uppercase transition-all hs-collapse-toggle text-default-900 open"
                                    data-hs-collapse="#popular_brands" id="hs-basic-collapse-popular" type="button">
                                Merek Populer
                            </button>
                            <div class="hs-collapse w-full overflow-hidden transition-[height] duration-300 open" id="popular_brands">
                                <div class="relative flex flex-col mb-6 space-y-5">
                                    <div class="flex gap-x-6">
                                        {{-- Kolom 1 --}}
                                        <div class="flex flex-col w-1/2 space-y-4">
                                            @foreach ($popularBrands as $brand)
                                                @if(($brand['col'] ?? 1) == 1)
                                                    <div class="flex items-center">
                                                        <input class="w-5 h-5 bg-transparent rounded cursor-pointer form-checkbox border-default-200 text-primary focus:ring-transparent checked:bg-primary"
                                                               id="brand_{{ $brand['id'] }}" name="brand[]"
                                                               value="{{ $brand['id'] }}" type="checkbox"
                                                               @checked($brand['checked'] ?? false)>
                                                        <label class="inline-flex items-center text-sm select-none ps-3 text-default-600"
                                                               for="brand_{{ $brand['id'] }}">{{ $brand['label'] }}</label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        {{-- Kolom 2 --}}
                                        <div class="flex flex-col w-1/2 space-y-4">
                                            @foreach ($popularBrands as $brand)
                                                @if(($brand['col'] ?? 2) == 2)
                                                    <div class="flex items-center">
                                                        <input class="w-5 h-5 bg-transparent rounded cursor-pointer form-checkbox border-default-200 text-primary focus:ring-transparent checked:bg-primary"
                                                               id="brand2_{{ $brand['id'] }}" name="brand[]"
                                                               value="{{ $brand['id'] }}" type="checkbox"
                                                               @checked($brand['checked'] ?? false)>
                                                        <label class="inline-flex items-center text-sm select-none ps-3 text-default-600"
                                                               for="brand2_{{ $brand['id'] }}">{{ $brand['label'] }}</label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- OFFER BOX (statis, boleh ganti sesuai kebutuhan) --}}
                        <div class="py-6">
                            <div class="relative rounded-lg bg-[url(../images/other/offer-bg.png)] bg-opacity-5 bg-center bg-cover overflow-hidden">
                                <div class="absolute inset-0 bg-primary/10 -z-10"></div>
                                <div class="p-12">
                                    <div class="flex justify-center mb-6">
                                        <img src="https://res.cloudinary.com/dqta7pszj/image/upload/v1740024751/rclmcqugizwgji0550x4.png" alt="">
                                    </div>
                                    <div class="mb-10 text-center">
                                        <h3 class="mb-2 text-2xl font-medium text-default-900">Diskon Liquid Khusus</h3>
                                        <p class="text-sm text-default-500">Dapatkan liquid freebase premium dengan harga terbaik minggu ini!</p>
                                    </div>
                                    <div class="flex items-center justify-center w-full gap-2 mb-6 font-medium text-default-950">
                                        Harga Mulai :
                                        <span class="inline-flex items-center gap-4 px-4 py-2 text-sm rounded-full xl:px-5 bg-default-50">
                                            Rp. 119.000
                                        </span>
                                    </div>
                                    <a href="{{ url('products?category=liquid') }}"
                                       class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-full bg-primary text-white hover:bg-primary-500 transition-all">
                                        Beli Sekarang <i class="w-5 h-5" data-lucide="move-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div> {{-- /p-6 --}}
                </div> {{-- /scroll --}}

                {{-- Reset (mobile) --}}
                <div class="block px-4 py-4 border-t lg:hidden border-default-200">
                    <a class="w-full inline-flex items-center justify-center rounded border border-primary bg-primary px-6 py-2.5 text-center text-sm font-medium text-white shadow-sm transition-all hover:border-primary-700 hover:bg-primary focus:ring focus:ring-primary/50"
                       href="{{ url('products') }}">Reset Filter</a>
                </div>
            </form>

            {{-- =========================
                 GRID PRODUK
            ========================== --}}
            <div class="lg:w-3/4">
                {{-- Search Form --}}
                <div class="mb-6">
                    <form method="GET" action="{{ url('products') }}" class="relative">
                        {{-- Keep existing filters --}}
                        @if(!empty($activeFilters['category']))
                            @foreach((array)$activeFilters['category'] as $cat)
                                <input type="hidden" name="category[]" value="{{ $cat }}">
                            @endforeach
                        @endif
                        @if(!empty($activeFilters['brand']))
                            @foreach((array)$activeFilters['brand'] as $brand)
                                <input type="hidden" name="brand[]" value="{{ $brand }}">
                            @endforeach
                        @endif
                        @if(!empty($activeFilters['price']))
                            <input type="hidden" name="price" value="{{ $activeFilters['price'] }}">
                        @endif
                        @if(!empty($activeFilters['sort']))
                            <input type="hidden" name="sort" value="{{ $activeFilters['sort'] }}">
                        @endif

                        <div class="relative">
                            <!-- leading icon -->
                            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                                <i data-lucide="search" class="w-5 h-5 text-default-400"></i>
                            </span>

                            <input
                                name="search"
                                value="{{ $activeFilters['search'] ?? '' }}"
                                type="search"
                                autocomplete="off"
                                placeholder="Cari produk, brand, atau kategori..."
                                class="block w-full h-11 rounded-lg border border-default-200 text-sm
                                    ps-12 pe-12
                                    focus:border-indigo-500 focus:ring-indigo-500
                                    dark:bg-default-50 dark:border-default-200" />

                            @if(!empty($activeFilters['search']))
                                <!-- trailing clear button -->
                                <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}"
                                class="absolute inset-y-0 end-0 pe-4 grid place-items-center
                                        text-default-400 hover:text-default-600"
                                aria-label="Hapus pencarian">
                                <i data-lucide="x" class="w-5 h-5"></i>
                                </a>
                            @else
                                <!-- trailing submit button -->
                                <button type="submit"
                                        class="absolute inset-y-0 end-0 pe-4 grid place-items-center
                                            text-primary-600 hover:text-primary-700"
                                        aria-label="Cari">
                                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                                </button>
                            @endif
                        </div>

                    </form>
                </div>

                {{-- Active Search Result Badge --}}
                @if(!empty($activeFilters['search']))
                <div class="mb-6">
                    <div class="p-4 rounded-lg bg-primary-50 dark:bg-primary-900/10 border border-primary-200">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <i class="w-5 h-5 text-primary-600" data-lucide="search"></i>
                                <span class="text-sm text-default-700">
                                    Hasil pencarian untuk: <strong class="font-semibold text-default-900">"{{ $activeFilters['search'] }}"</strong>
                                </span>
                            </div>
                            <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}"
                               class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 transition-all">
                                Hapus <i class="w-4 h-4" data-lucide="x"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <div class="flex flex-wrap items-center justify-between gap-4 mb-10 md:flex-nowrap">
                    <div class="flex flex-wrap items-center gap-4 md:flex-nowrap">
                        <button class="inline-flex lg:hidden items-center gap-4 text-sm py-2.5 px-4 xl:px-5 rounded-full text-default-950 border border-default-200 transition-all"
                                data-hs-overlay="#filter_Offcanvas" type="button">
                            Filter <i class="w-4 h-4" data-lucide="settings-2"></i>
                        </button>

                        <h6 class="hidden text-base lg:flex text-default-950">
                            Menampilkan {{ $pagination->firstItem() ?? 0 }}–{{ $pagination->lastItem() ?? 0 }} dari {{ $totalProducts ?? 0 }} produk
                        </h6>
                    </div>

                    {{-- Sorting --}}
                    <div class="flex items-center">
                        <span class="text-base text-default-950 me-3">Urutkan berdasarkan :</span>
                        <div class="hs-dropdown relative inline-flex [--placement:bottom-left]">
                            <button class="hs-dropdown-toggle flex items-center gap-2 font-medium text-default-950 text-sm py-2.5 px-4 xl:px-5 rounded-full border border-default-200 transition-all" type="button">
                                {{ $sortLabel }} <i class="w-4 h-4" data-lucide="chevron-down"></i>
                            </button>
                            <div class="hs-dropdown-menu hs-dropdown-open:opacity-100 min-w-[220px] transition-[opacity,margin] mt-4 opacity-0 hidden z-20 bg-white shadow-[rgba(17,17,26,0.1)_0px_0px_16px] rounded-lg border border-default-100 p-1.5 dark:bg-default-50">
                                @php
                                    $sortOptions = [
                                        'newest'      => 'Terbaru',
                                        'best_seller' => 'Terlaris',
                                        'price_asc'   => 'Harga Termurah',
                                        'price_desc'  => 'Harga Termahal',
                                        'rating_desc' => 'Rating Tertinggi',
                                    ];
                                @endphp
                                <ul class="flex flex-col gap-1">
                                    @foreach ($sortOptions as $key => $label)
                                        @php
                                            $href = request()->fullUrlWithQuery(['sort' => $key, 'page' => 1]);
                                        @endphp
                                        <li>
                                            <a class="flex items-center gap-3 px-3 py-2 font-normal transition-all rounded {{ $sortActive === $key ? 'text-default-700 bg-default-400/20' : 'text-default-600 hover:text-default-700 hover:bg-default-400/20' }}"
                                               href="{{ $href }}">{{ $label }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- GRID --}}
                <div class="grid grid-cols-2 gap-5 xl:grid-cols-4 md:grid-cols-3">
                    @forelse ($products as $p)
                        <div class="order-2 p-4 overflow-hidden transition-all duration-300 border rounded-lg xl:order-1 border-default-200 hover:border-primary hover:shadow-xl">
                            <div class="relative overflow-hidden divide-y rounded-lg divide-default-200 group">
                                <div class="flex justify-center mx-auto mb-4">
                                    <a href="{{ $p['url'] ?? '#' }}" class="block">
                                        <img class="w-1/3 h-1/3 transition-all group-hover:scale-105"
                                             src="{{ $p['image'] ?? 'https://placehold.co/600x600?text=OGITU' }}"
                                             alt="{{ $p['title'] ?? 'Produk' }}">
                                    </a>
                                </div>
                                <div class="pt-2">
                                    <div class="items-center mb-1">
                                        <a class="relative text-xl font-semibold text-default-800 line-clamp-1 after:absolute after:inset-0"
                                           href="{{ $p['url'] ?? '#' }}">{{ $p['title'] ?? '-' }}</a>
                                    </div>
                                    <p class="mb-3 text-xs text-default-500">
                                        {{ $p['store_name'] ?? 'Official' }}
                                    </p>
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-1">
                                            <span class="flex items-center justify-center p-1 rounded-full bg-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                     class="w-3 h-3 text-white" fill="currentColor">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                                </svg>
                                            </span>
                                            <span class="text-sm text-default-950">{{ $p['rating'] ?? '4.6' }}</span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-default-900">{{ $p['price'] ?? 'Rp. 0' }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 md:col-span-3 xl:col-span-4">
                            <div class="p-8 text-center border rounded-lg border-default-200">
                                <p class="font-medium text-default-700">Produk tidak ditemukan. Coba ubah filter kamu.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- PAGINATION --}}
                <div class="flex items-center justify-between pt-6">
                    <div class="hidden md:block">
                        {{-- Laravel default links (tailwind) --}}
                        {{ $pagination->onEachSide(1)->links() }}
                    </div>
                    {{-- Tombol prev/next ringkas --}}
                    <div class="md:hidden flex items-center gap-2">
                        @if ($pagination->onFirstPage())
                            <span class="inline-flex items-center justify-center rounded-full h-9 w-9 bg-default-100 text-default-400">
                                <i class="w-5 h-5" data-lucide="chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $pagination->previousPageUrl() }}"
                               class="inline-flex items-center justify-center transition-all duration-500 rounded-full h-9 w-9 bg-default-100 text-default-800 hover:bg-primary hover:text-white">
                                <i class="w-5 h-5" data-lucide="chevron-left"></i>
                            </a>
                        @endif

                        @if ($pagination->hasMorePages())
                            <a href="{{ $pagination->nextPageUrl() }}"
                               class="inline-flex items-center justify-center transition-all duration-500 rounded-full h-9 w-9 bg-default-100 text-default-800 hover:bg-primary hover:text-white">
                                <i class="w-5 h-5" data-lucide="chevron-right"></i>
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center rounded-full h-9 w-9 bg-default-100 text-default-400">
                                <i class="w-5 h-5" data-lucide="chevron-right"></i>
                            </span>
                        @endif
                    </div>

                    {{-- Reset filter (desktop) --}}
                    <div class="hidden md:block">
                        <a href="{{ url('products') }}" class="text-sm text-primary hover:text-primary-500">Reset semua filter</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Auto submit on change + aturan "Semua Kategori" --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filterForm');
    if (!form) return;

    // submit on change untuk semua input filter
    form.querySelectorAll('input[type=checkbox], input[type=radio]').forEach(el => {
        el.addEventListener('change', () => {
            // aturan khusus "Semua Kategori"
            if (el.id === 'cat_all') {
                if (el.checked) {
                    form.querySelectorAll('input[name="category[]"]').forEach(c => c.checked = false);
                }
            } else if (el.name === 'category[]' && el.checked) {
                const all = document.getElementById('cat_all');
                if (all) all.checked = false;
            }
            form.submit();
        });
    });
});
</script>
@endsection
