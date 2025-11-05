@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Arr;
        use App\Models\ProductReview;

        /** @var array $product */
        $brandName = Arr::get($product, 'brand.name', 'Official');
        $category = Arr::get($product, 'category.name');

        // Gambar utama dari controller (variabel $image), fallback placeholder
        $mainImage = $image ?? 'https://placehold.co/600x600?text=OGITU';

        // Galeri: karena payload tidak menyertakan galeri, pakai 1 gambar utama
        $gallery = [$mainImage];

        // Deskripsi
        $desc = Arr::get($product, 'description', '');

        // Varian aktif (array)
        $variantOptions = collect(Arr::get($product, 'product_variants', []))
            ->filter(fn($v) => Arr::get($v, 'is_active') === true)
            ->values();

        $defaultVariant = $variantOptions->first();

        // Hitung total & distribusi rating untuk produk ini
        $ratingRows = ProductReview::query()
            ->join('order_items', 'order_items.id', '=', 'product_reviews.order_item_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('product_variants.product_id', Arr::get($product, 'id'))
            ->selectRaw('product_reviews.rating as r, COUNT(*) as c')
            ->groupBy('product_reviews.rating')
            ->pluck('c', 'r')
            ->all();

        $ratingCounts = [
            5 => (int) ($ratingRows[5] ?? 0),
            4 => (int) ($ratingRows[4] ?? 0),
            3 => (int) ($ratingRows[3] ?? 0),
            2 => (int) ($ratingRows[2] ?? 0),
            1 => (int) ($ratingRows[1] ?? 0),
        ];
        $totalRatings = array_sum($ratingCounts);

        // Ambil 5 review terbaru (opsional)
        $latestReviews = ProductReview::query()
            ->join('order_items', 'order_items.id', '=', 'product_reviews.order_item_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('product_variants.product_id', Arr::get($product, 'id'))
            ->latest('product_reviews.id')->limit(5)
            ->get(['product_reviews.rating', 'product_reviews.comment', 'product_reviews.created_at']);

        // Helper
        $idr = fn(int|float $n) => 'Rp. ' . number_format($n, 0, ',', '.');
        $viewingNow = max(12, min(300, (Arr::get($product, 'id', 37) * 13) % 197 + 12));
        $renderStarsSvg = function (float $score, int $max = 5) {
            $solidStar = '<svg class="w-4 h-4 inline-block text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21a.75.75 0 0 1 1.424 0l2.082 6.405a.75.75 0 0 0 .712.516h6.735a.75.75 0 0 1 .44 1.356l-5.45 3.959a.75.75 0 0 0-.272.838l2.082 6.405a.75.75 0 0 1-1.154.838l-5.45-3.959a.75.75 0 0 0-.88 0l-5.45 3.959a.75.75 0 0 1-1.154-.838l2.082-6.405a.75.75 0 0 0-.272-.838l-5.45-3.959a.75.75 0 0 1 .44-1.356h6.735a.75.75 0 0 0 .712-.516l2.082-6.405Z" clip-rule="evenodd" /></svg>';
            $emptyStar = '<svg class="w-4 h-4 inline-block text-gray-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21a.75.75 0 0 1 1.424 0l2.082 6.405a.75.75 0 0 0 .712.516h6.735a.75.75 0 0 1 .44 1.356l-5.45 3.959a.75.75 0 0 0-.272.838l2.082 6.405a.75.75 0 0 1-1.154.838l-5.45-3.959a.75.75 0 0 0-.88 0l-5.45 3.959a.75.75 0 0 1-1.154-.838l2.082-6.405a.75.75 0 0 0-.272-.838l-5.45-3.959a.75.75 0 0 1 .44-1.356h6.735a.75.75 0 0 0 .712-.516l2.082-6.405Z" clip-rule="evenodd" /></svg>';
            $halfStar = '<svg class="w-4 h-4 inline-block text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><defs><linearGradient id="half-star"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="rgb(209 213 219)" stop-opacity="1"/></linearGradient></defs><path fill="url(#half-star)" fill-rule="evenodd" d="M10.788 3.21a.75.75 0 0 1 1.424 0l2.082 6.405a.75.75 0 0 0 .712.516h6.735a.75.75 0 0 1 .44 1.356l-5.45 3.959a.75.75 0 0 0-.272.838l2.082 6.405a.75.75 0 0 1-1.154.838l-5.45-3.959a.75.75 0 0 0-.88 0l-5.45 3.959a.75.75 0 0 1-1.154-.838l2.082-6.405a.75.75 0 0 0-.272-.838l-5.45-3.959a.75.75 0 0 1 .44-1.356h6.735a.75.75 0 0 0 .712-.516l2.082-6.405Z" clip-rule="evenodd" /></svg>';

            $full = floor($score);
            $half = ($score - $full) >= 0.5 ? 1 : 0;
            $empty = $max - $full - $half;

            $output = str_repeat($solidStar, (int) $full);
            if ($half) {
                $output .= $halfStar;
            }
            $output .= str_repeat($emptyStar, (int) $empty);

            return $output;
        };
    @endphp

    <section class="py-6 lg:py-10">
        <div class="container">
            <div class="grid gap-6 lg:grid-cols-2">
                {{-- GALERI --}}
                <div class="grid grid-cols-1">
                    <div>
                        <div class="swiper cart-swiper">
                            <div class="swiper-wrapper">
                                @foreach($gallery as $img)
                                    <div class="swiper-slide">
                                        <img src="{{ $img }}" class="h-full max-w-full mx-auto rounded-2xl"
                                            alt="{{ $product['name'] ?? 'Produk' }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="justify-center swiper cart-swiper-pagination mt-5">
                        <div class="justify-center w-full gap-2 swiper-wrapper">
                            @foreach($gallery as $img)
                                <div class="swiper-slide cursor-pointer w-24 h-24 lg:w-32 lg:h-32 rounded-2xl">
                                    <img src="{{ $img }}" class="w-full h-full rounded" alt="thumb {{ $loop->iteration }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- INFORMASI PRODUK --}}
                <div>
                    <h3 class="mb-1 text-4xl font-medium text-default-800">{{ $product['name'] ?? 'Produk' }}</h3>
                    <h5 class="mb-2 text-lg font-medium text-default-600">
                        <span class="text-base font-normal text-default-500">Dari</span> {{ $brandName }}
                        @if($category)
                            <span class="mx-2 text-default-400">•</span>
                            <span class="text-base font-normal text-default-500">{{ $category }}</span>
                        @endif
                    </h5>

                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex gap-1.5">{!! $renderStarsSvg((float) ($avgRating ?? 0)) !!}</div>
                        <div class="w-px h-4 bg-default-400"></div>
                        <h5 class="text-sm text-default-500">
                            {{ number_format($totalRatings) }} Reviews
                        </h5>
                    </div>

                    @if($desc)
                        <p class="mb-4 text-sm text-default-500">{{ $desc }}</p>
                    @endif

                    {{-- TAGS sederhana: brand & kategori --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        @if($brandName)
                            <div class="border border-default-200 rounded-full px-3 py-1.5 flex items-center">
                                <span class="text-xs">Brand: {{ $brandName }}</span>
                            </div>
                        @endif
                        @if($category)
                            <div class="border border-default-200 rounded-full px-3 py-1.5 flex items-center">
                                <span class="text-xs">Kategori: {{ $category }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- ADD TO CART LIVEWIRE COMPONENT --}}
                    @livewire('ecommerce.add-to-cart', [
                        'variantId' => Arr::get($defaultVariant, 'id'),
                        'variantOptions' => $variantOptions->toArray()
                    ])

                    {{-- SPESIFIKASI SINGKAT --}}
                    <div class="mb-6">
                        <h4 class="mb-4 text-lg font-medium text-default-700">
                            Spesifikasi <span class="text-sm text-default-400">(produk)</span>
                        </h4>
                        <div class="p-3 border rounded-lg border-default-200">
                            <div class="grid justify-center grid-cols-3">
                                <div class="text-center">
                                    <h4 class="text-base text-default-700">Brand</h4>
                                    <h4 class="mb-1 text-base font-medium text-default-700">{{ $brandName }}</h4>
                                </div>
                                <div class="text-center">
                                    <h4 class="text-base text-default-700">Kategori</h4>
                                    <h4 class="mb-1 text-base font-medium text-default-700">{{ $category ?? '-' }}</h4>
                                </div>
                                <div class="text-center">
                                    <h4 class="text-base text-default-700">SKU</h4>
                                    <h4 class="mb-1 text-base font-medium text-default-700">
                                        {{ Arr::get($defaultVariant, 'sku', '-') }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- VIEWING NOW --}}
                    <div class="flex items-center">
                        <i data-lucide="eye" class="w-5 h-5 me-2 text-primary"></i>
                        <h5 class="text-sm text-default-600">
                            <span class="font-semibold text-primary">{{ number_format($viewingNow) }}</span>&nbsp; orang
                            sedang melihat produk ini
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- PRODUK TERKAIT (tampilkan hanya jika disediakan dari controller) --}}
    @if(!empty($related ?? []))
        <section class="py-6 lg:py-10">
            <div class="container">
                <h4 class="mb-4 text-xl font-semibold text-default-800">Produk yang mungkin kamu suka</h4>
                <div class="grid gap-5 mb-10 xl:grid-cols-4 sm:grid-cols-2">
                    @foreach($related as $rp)
                        <div
                            class="p-4 overflow-hidden transition-all duration-300 border rounded-lg group border-default-200 hover:border-primary">
                            <div class="relative overflow-hidden divide-y rounded-lg divide-default-200">
                                <div class="w-56 mx-auto mb-4 h-52">
                                    <img src="{{ $rp['image'] }}" class="w-full h-full transition-all group-hover:scale-105"
                                        alt="{{ $rp['title'] }}">
                                </div>
                                <div class="pt-2">
                                    <div class="items-center mb-4">
                                        <a class="text-xl font-semibold text-default-800 line-clamp-1 after:absolute after:inset-0"
                                            href="{{ $rp['url'] }}">{{ $rp['title'] }}</a>
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
                                            <span class="text-sm text-default-950">{{ number_format($rp['rating'], 1) }}</span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-default-900">{{ $idr($rp['price']) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- CUSTOMER RATING --}}
    <section class="py-6 lg:py-10">
        <div class="container">
            <h4 class="mb-4 text-xl font-semibold text-default-800">Customer Rating</h4>
            @php
                $rows = [
                    5 => $ratingCounts[5] ?? 0,
                    4 => $ratingCounts[4] ?? 0,
                    3 => $ratingCounts[3] ?? 0,
                    2 => $ratingCounts[2] ?? 0,
                    1 => $ratingCounts[1] ?? 0,
                ];
            @endphp
            <div class="grid items-center gap-5 lg:grid-cols-4">
                <div class="flex flex-col items-center justify-center py-8 rounded-lg bg-primary/10">
                    <h1 class="mb-4 text-6xl font-semibold text-default-800">
                        {{ number_format((float) ($avgRating ?? 0), 1) }}
                    </h1>
                    <div class="flex gap-1.5 mb-2">{!! $renderStarsSvg((float) ($avgRating ?? 0)) !!}</div>
                    <h4 class="text-base font-medium text-default-700">
                        Customer Rating <span
                            class="font-normal text-default-500">({{ number_format($totalRatings) }})</span>
                    </h4>
                </div>

                <div class="xl:col-span-2 md:col-span-3">
                    @forelse($rows as $stars => $count)
                        @php
                            $percent = $totalRatings ? round($count / $totalRatings * 100) : 0;
                        @endphp
                        <div class="grid items-center gap-2 mb-3 md:grid-cols-12">
                            <div class="md:col-span-3 flex gap-1.5 lg:justify-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        class="w-4 h-4 {{ $i <= $stars ? 'text-yellow-400' : 'text-gray-300' }}"
                                        fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10.788 3.21a.75.75 0 0 1 1.424 0l2.082 6.405a.75.75 0 0 0 .712.516h6.735a.75.75 0 0 1 .44 1.356l-5.45 3.959a.75.75 0 0 0-.272.838l2.082 6.405a.75.75 0 0 1-1.154.838l-5.45-3.959a.75.75 0 0 0-.88 0l-5.45 3.959a.75.75 0 0 1-1.154-.838l2.082-6.405a.75.75 0 0 0-.272-.838l-5.45-3.959a.75.75 0 0 1 .44-1.356h6.735a.75.75 0 0 0 .712-.516l2.082-6.405Z" clip-rule="evenodd" />
                                    </svg>
                                @endfor
                            </div>
                            <div class="md:col-span-7">
                                <div class="flex w-full h-1 overflow-hidden rounded-full bg-default-200">
                                    <div class="flex flex-col justify-center overflow-hidden rounded bg-primary"
                                        role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
                                        style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <h4 class="inline-block text-sm font-medium text-default-700">{{ $percent }}%</h4>
                                <span class="font-normal text-default-500">({{ number_format($count) }})</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-default-500">Belum ada rating.</p>
                    @endforelse
                </div>
            </div>

            {{-- CUSTOMER REVIEWS --}}
            <div class="pt-10">
                <h4 class="text-base font-medium text-default-800">Customer Review</h4>
                @forelse($latestReviews as $rev)
                    @php
                        $text = $rev->comment ?? $rev->review ?? '';
                    @endphp
                    <div class="py-5 border-b border-default-200">
                        <div class="flex items-center mb-3">
                            <img src="{{ asset('assets/images/avatar1-25906796.png') }}" class="w-12 h-12 rounded-full me-4"
                                alt="User">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="text-sm font-medium text-default-800">Pengguna</h4>
                                    <i class="fa-solid fa-circle text-[5px] text-default-400"></i>
                                    <h4 class="text-sm font-medium text-default-400">
                                        {{ optional($rev->created_at)->diffForHumans() }}
                                    </h4>
                                </div>
                                <div class="flex gap-1.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            class="w-4 h-4 {{ $i <= (int) $rev->rating ? 'text-yellow-400' : 'text-gray-300' }}"
                                            fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M10.788 3.21a.75.75 0 0 1 1.424 0l2.082 6.405a.75.75 0 0 0 .712.516h6.735a.75.75 0 0 1 .44 1.356l-5.45 3.959a.75.75 0 0 0-.272.838l2.082 6.405a.75.75 0 0 1-1.154.838l-5.45-3.959a.75.75 0 0 0-.88 0l-5.45 3.959a.75.75 0 0 1-1.154-.838l2.082-6.405a.75.75 0 0 0-.272-.838l-5.45-3.959a.75.75 0 0 1 .44-1.356h6.735a.75.75 0 0 0 .712-.516l2.082-6.405Z" clip-rule="evenodd" />
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        <p class="text-default-600">{{ $text ?: 'Tidak ada komentar.' }}</p>
                    </div>
                @empty
                    <p class="mt-4 text-sm text-default-500">Belum ada ulasan untuk produk ini.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
