<section>
    <div class="min-h-screen w-full text-gray-900">
        <div class="container mx-auto px-4 py-12">
            {{-- Header --}}
            <header class="mb-12 text-center">
                <h1 class="mb-3 text-balance text-5xl font-extrabold text-gray-900 dark:text-white">Blog Terbaru</h1>
                <p class="mx-auto max-w-2xl text-balance text-xl text-gray-600 dark:text-gray-400">
                    Temukan wawasan terbaru, panduan mendalam, dan berita teknologi dari tim kami.
                </p>
            </header>

            {{-- Search Bar --}}
            <form role="search" class="mx-auto flex w-full max-w-lg items-center gap-2">
                <label for="voice-search" class="sr-only">Cari artikel</label>

                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14 2H6a2 2 0 0 0-2 2v16c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6Z" />
                            <path stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" d="M14 2v6h6" />
                            <path stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                d="M8 13h8M8 17h8M8 9h2" />
                        </svg>
                    </div>

                    <input id="voice-search" type="text" wire:model.live.debounce.500ms="search"
                        placeholder="Cari artikel..." required autocapitalize="off" autocomplete="off" autocorrect="off"
                        spellcheck="false"
                        class="ps-10 pe-4 py-2.5 block w-96 border-transparent placeholder-primary-500 rounded-full text-sm bg-primary-200/40 dark:bg-default-200 text-primary focus:ring-2 focus:ring-primary-500" />

                    @if($search)
                        <button type="button" wire:click="$set('search', '')" aria-label="Bersihkan pencarian"
                            class="relative inline-flex items-center justify-center w-full px-6 py-3 text-base text-white capitalize transition-all rounded-full bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 16 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 7v3a5.006 5.006 0 0 1-5 5H6a5.006 5.006 0 0 1-5-5V7m7 9v3m-3 0h6M7 1h2a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V4a3 3 0 0 1 3-3Z" />
                            </svg>
                        </button>
                    @endif
                </div>

                <button type="submit" class="relative inline-flex items-center justify-center w-full px-6 py-3 text-base text-white capitalize transition-all rounded-full bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    Cari
                </button>
            </form>

            {{-- Category Filters --}}
            <div class="mt-5 mb-12 flex flex-wrap justify-center gap-3">
                {{-- All Articles --}}
                <label class="inline-flex">
                    <input type="radio" name="categoryFilter" class="peer sr-only" @checked(!$selectedCategory)>
                    <button type="button" wire:click="filterByCategory(null)" class="relative inline-flex items-center justify-center w-full px-6 py-3 text-base text-white capitalize transition-all rounded-full bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        Semua Artikel
                        @if(!$selectedCategory && $articles->total() > 0)
                            <span class="ml-1 text-xs opacity-75">({{ $articles->total() }})</span>
                        @endif
                    </button>
                </label>

                {{-- Per-category --}}
                @foreach ($categories as $category)
                    @php $isActive = $selectedCategory === $category->slug; @endphp
                    <label class="inline-flex">
                        <input type="radio" name="categoryFilter" class="peer sr-only" @checked($isActive)>
                        <button type="button" wire:click="filterByCategory('{{ $category->slug }}')" class="relative inline-flex items-center justify-center w-full px-6 py-3 text-base text-white capitalize transition-all rounded-full bg-primary hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ $category->name }}
                            <span class="ml-1 text-xs opacity-75">({{ $category->articles_count }})</span>
                        </button>
                    </label>
                @endforeach
            </div>

            {{-- Active Filters Info --}}
            @if($search || $selectedCategory)
                <div class="mb-6 flex items-center justify-center gap-3 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Filter aktif:</span>

                    @if($search)
                        <span class="inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-primary-700
                                                dark:bg-primary-900/40 dark:text-primary-300">
                            Pencarian: "{{ $search }}"
                            <button type="button" wire:click="$set('search', '')" aria-label="Bersihkan pencarian"
                                class="inline-flex items-center justify-center rounded-md p-1
                                                   text-gray-400 hover:text-primary-600 transition
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                                                   focus:ring-offset-white
                                                   dark:text-gray-400 dark:hover:text-primary-400 dark:focus:ring-offset-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if($selectedCategory)
                        @php $selectedCat = collect($categories)->firstWhere('slug', $selectedCategory); @endphp
                        <span class="inline-flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-primary-700
                                                dark:bg-primary-900/40 dark:text-primary-300">
                            Kategori: {{ $selectedCat?->name ?? $selectedCategory }}
                            <button type="button" wire:click="filterByCategory(null)"
                                class="hover:text-primary-900 dark:hover:text-primary-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    @endif

                    <button type="button" wire:click="clearFilters" class="underline text-gray-500 hover:text-gray-700
                                   dark:text-gray-400 dark:hover:text-gray-300">
                        Hapus semua filter
                    </button>
                </div>
            @endif

            {{-- Loading Indicator --}}
            <div wire:loading.flex class="items-center justify-center py-8">
                <svg class="h-8 w-8 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            {{-- Articles Grid --}}
            <div wire:loading.remove class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($articles as $article)
                        <div class="order-2 xl:order-1">
                            <article class="group overflow-hidden rounded-xl border border-default-200 shadow-sm
                          transition hover:shadow-xl">
                                <a href="{{ route('articles.show', $article->slug) }}" class="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                      focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                                    aria-labelledby="art-{{ $article->id }}-title">

                                    {{-- Cover --}}
                                    <figure class="relative aspect-video overflow-hidden">
                                        @if($article->cover_url)
                                            <img src="{{ asset('storage/' . $article->cover_url) }}"
                                                alt="{{ $article->cover_alt ?? $article->title }}" loading="lazy"
                                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]" />
                                        @else
                                            <div
                                                class="grid h-full w-full place-items-center bg-linear-to-br from-primary-400 to-primary-600">
                                                <svg class="h-16 w-16" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif

                                        {{-- Category badge (opsional) --}}
                                        @if($article->category)
                                                    <figcaption class="pointer-events-none absolute left-4 top-4">
                                                        <span class="inline-block rounded-full bg-primary px-3 py-1 text-xs font-semibold dark:bg-primary-600">
                                                            {{ $article->category->name }}
                                                        </span>
                                                    </figcaption>
                                        @endif
                                    </figure>

                                    {{-- Body --}}
                                    <div class="p-5">
                                        <h3 id="art-{{ $article->id }}-title" class="mb-2 line-clamp-2 text-xl font-bold text-default-900 hover:text-primary
                           transition-colors dark:hover:text-primary-400">
                                            {{ $article->title }}
                                        </h3>

                                        @if(!empty($article->excerpt))
                                            <p class="mb-4 line-clamp-3 text-sm text-default-600">
                                                {{ $article->excerpt }}
                                            </p>
                                        @endif

                                        {{-- Meta --}}
                                        <div
                                            class="flex items-center justify-between text-xs text-default-500">
                                            <span>
                                                @if($article->author) Oleh: {{ $article->author->name }} @else Admin @endif
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <time datetime="{{ optional($article->published_at)->toDateString() }}">
                                                    {{ optional($article->published_at)->format('d M Y') }}
                                                </time>
                                                <span aria-hidden="true">•</span>
                                                <span>{{ $article->read_time }} min baca</span>
                                            </div>
                                        </div>

                                        {{-- Tags (maks 3) --}}
                                        @if(!empty($article->tags) && count($article->tags) > 0)
                                            <ul class="mt-3 flex flex-wrap gap-1">
                                                @foreach(array_slice($article->tags, 0, 3) as $tag)
                                                                <li class="inline-block rounded bg-default-100 px-2 py-1 text-xs text-default-600">
                                                                    #{{ $tag }}
                                                                </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </a>
                            </article>
                        </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <svg class="mx-auto mb-4 h-16 w-16 text-gray-300 dark:text-gray-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <p class="mb-2 text-lg text-gray-500 dark:text-gray-400">Tidak ada artikel yang ditemukan</p>
                        @if($search || $selectedCategory)
                            <button type="button" wire:click="clearFilters"
                                class="underline text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                Hapus filter dan tampilkan semua artikel
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($articles->hasPages())
                <div class="mt-12">
                    {{ $articles->links() }}
                </div>
            @endif

            {{-- Results Count --}}
            @if($articles->total() > 0)
                <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    Menampilkan {{ $articles->firstItem() }} - {{ $articles->lastItem() }} dari {{ $articles->total() }}
                    artikel
                </div>
            @endif
        </div>
    </div>
</section>
