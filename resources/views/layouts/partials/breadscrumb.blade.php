<section class="lg:flex items-center hidden bg-default-400/10 h-14">
    <div class="container">
        <div class="flex items-center">
            @php
                // Ambil segmen URL, contoh: /product/category/slug -> ['product','category','slug']
                $segments = request()->segments();

                // Opsi kustomisasi label per segmen (opsional)
                // Kirim dari view sebagai $breadcrumbMap = ['product' => 'Produk', 'blog' => 'Artikel', ...]
                $map = $breadcrumbMap ?? [];

                // Title khusus untuk segmen terakhir (opsional), mis. judul artikel/produk
                // Kirim dari view sebagai $breadcrumbTitle = $post->title;
                $finalTitle = $breadcrumbTitle ?? null;

                // Helper label: map -> humanize (ganti '-'/'_' jadi spasi + title case)
                $labelFor = function ($seg, $isLast) use ($map, $finalTitle) {
                    if ($isLast && $finalTitle) {
                        return $finalTitle;
                    }
                    if (isset($map[$seg])) {
                        return $map[$seg];
                    }
                    // Jika segmen murni angka & ini terakhir, tampilkan 'Detail'
                    if ($isLast && ctype_digit($seg)) {
                        return 'Detail';
                    }
                    return ucwords(str_replace(['-', '_'], ' ', $seg));
                };

                // Bangun URL bertahap untuk tiap crumb
                $buildUrl = function ($i) use ($segments) {
                    return url(implode('/', array_slice($segments, 0, $i + 1)));
                };
            @endphp

            <ol aria-label="Breadcrumb" class="flex items-center whitespace-nowrap min-w-0 gap-2">
                {{-- Home --}}
                <li class="text-sm">
                    <a class="flex items-center gap-2 align-middle text-default-800 transition-all leading-none hover:text-primary-500"
                       href="{{ url('/') }}">
                        <i class="w-4 h-4" data-lucide="home"></i>
                        Home
                        @if(count($segments))
                            <i class="w-4 h-4" data-lucide="chevron-right"></i>
                        @endif
                    </a>
                </li>

                {{-- Dynamic crumbs dari URL --}}
                @foreach ($segments as $i => $seg)
                    @php
                        $isLast = $i === count($segments) - 1;
                        $label  = $labelFor($seg, $isLast);

                        // Buat URL kumulatif untuk crumb ini
                        $href = $buildUrl($i);
                    @endphp

                    @if (!$isLast)
                        <li class="text-sm">
                            <a class="flex items-center gap-2 align-middle text-default-800 transition-all leading-none hover:text-primary-500"
                               href="{{ $href }}">
                                {{ $label }}
                                <i class="w-4 h-4" data-lucide="chevron-right"></i>
                            </a>
                        </li>
                    @else
                        <li aria-current="page"
                            class="text-sm font-medium text-primary truncate leading-none hover:text-primary-500">
                            {{ $label }}
                        </li>
                    @endif
                @endforeach
            </ol>
        </div>
    </div>
</section>
