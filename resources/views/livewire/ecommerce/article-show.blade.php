{{-- SEO Meta Tags --}}
@push('meta')
    @if($article->meta_description)
        <meta name="description" content="{{ $article->meta_description }}">
    @elseif($article->excerpt)
        <meta name="description" content="{{ $article->excerpt }}">
    @endif

    @if($article->meta_keywords && count($article->meta_keywords) > 0)
        <meta name="keywords" content="{{ implode(', ', $article->meta_keywords) }}">
    @endif

    <meta name="robots" content="{{ $article->noindex ? 'noindex' : 'index' }}, {{ $article->nofollow ? 'nofollow' : 'follow' }}">

    @if($article->canonical_url)
        <link rel="canonical" href="{{ $article->canonical_url }}">
    @else
        <link rel="canonical" href="{{ $article->url }}">
    @endif

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->seo_title ?: $article->title }}">
    <meta property="og:description" content="{{ $article->meta_description ?: $article->excerpt }}">
    <meta property="og:url" content="{{ $article->url }}">
    @if($article->cover_url)
        <meta property="og:image" content="{{ $article->cover_url }}">
    @endif
    @if($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toISOString() }}">
    @endif
    @if($article->author)
        <meta property="article:author" content="{{ $article->author->name }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->seo_title ?: $article->title }}">
    <meta name="twitter:description" content="{{ $article->meta_description ?: $article->excerpt }}">
    @if($article->cover_url)
        <meta name="twitter:image" content="{{ $article->cover_url }}">
    @endif
@endpush

<main class="pt-8 pb-16 lg:pt-16 lg:pb-24 antialiased">
    <div class="flex justify-between px-4 mx-auto max-w-7xl">
        <article
            class="mx-auto w-full max-w-6xl format format-sm sm:format-base lg:format-lg format-blue dark:format-invert">

            {{-- Header Section --}}
            <header class="mb-4 lg:mb-6 not-format">
                {{-- Author Info --}}
                <address class="flex items-center mb-6 not-italic">
                    <div class="inline-flex items-center mr-3 text-sm">
                        @if($article->author)
                            <img class="mr-4 w-16 h-16 rounded-full object-cover"
                                src="{{ $article->author->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($article->author->name) }}"
                                alt="{{ $article->author->name }}">
                            <div>
                                <a href="#" rel="author" class="text-xl font-bold">
                                    {{ $article->author->name }}
                                </a>
                                @if($article->author->bio)
                                    <p class="text-base">{{ $article->author->bio }}</p>
                                @endif
                                <p class="text-base">
                                    <time pubdate datetime="{{ $article->published_at->toISOString() }}"
                                        title="{{ $article->published_at->format('F j, Y') }}">
                                        {{ $article->published_at->format('M. d, Y') }}
                                    </time>
                                    <span class="mx-2">•</span>
                                    <span>{{ $article->read_time }} min read</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </address>

                {{-- Title --}}
                <h1 class="mb-4 text-3xl font-extrabold leading-tight lg:mb-6 lg:text-4xl">
                    {{ $article->title }}
                </h1>

                {{-- Categories --}}
                @if($article->category || $article->categories->count() > 0)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($article->category)
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-purple-600 rounded-full">
                                {{ $article->category->name }}
                            </span>
                        @endif
                        @foreach($article->categories as $category)
                            @if(!$article->category || $category->id !== $article->category->id)
                                <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-purple-600 rounded-full">
                                    {{ $category->name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Cover Image --}}
                @if($article->cover_url)
                    <figure class="mb-6">
                        <img src="{{ $article->cover_url }}"
                             alt="{{ $article->cover_alt ?? $article->title }}"
                             class="w-full rounded-lg">
                        @if($article->cover_alt)
                            <figcaption class="mt-2 text-center text-sm">
                                {{ $article->cover_alt }}
                            </figcaption>
                        @endif
                    </figure>
                @endif
            </header>

            {{-- Excerpt --}}
            @if($article->excerpt)
                <p class="lead text-lg font-medium mb-6">
                    {{ $article->excerpt }}
                </p>
            @endif

            {{-- Content Blocks --}}
            @if(is_array($article->content))
                @foreach($article->content as $block)
                    @if($block['type'] === 'heading')
                        @php
                            $level = $block['data']['level'] ?? 'h2';
                            $headingContent = $block['data']['content'] ?? '';
                        @endphp
                        <{{ $level }} class=">{{ $headingContent }}</{{ $level }}>

                    @elseif($block['type'] === 'paragraph')
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $block['data']['content'] ?? '' !!}
                        </div>

                    @elseif($block['type'] === 'list')
                        @php
                            $listStyle = $block['data']['style'] ?? 'unordered';
                            $items = $block['data']['items'] ?? [];
                        @endphp
                        @if($listStyle === 'ordered')
                            <ol class="list-decimal list-inside space-y-2">
                                @foreach($items as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ol>
                        @else
                            <ul class="list-disc list-inside space-y-2">
                                @foreach($items as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif

                    @elseif($block['type'] === 'image')
                        <figure class="my-6">
                            <img src="{{ $block['data']['url'] ?? '' }}"
                                 alt="{{ $block['data']['caption'] ?? '' }}"
                                 class="w-full rounded-lg">
                            @if(!empty($block['data']['caption']))
                                <figcaption class="mt-2 text-center text-sm">
                                    {{ $block['data']['caption'] }}
                                </figcaption>
                            @endif
                        </figure>

                    @elseif($block['type'] === 'quote')
                        <blockquote class="p-4 my-6 border-l-4">
                            <p class="text-lg italic font-medium leading-relaxed">
                                {{ $block['data']['content'] ?? '' }}
                            </p>
                            @if(!empty($block['data']['author']))
                                <cite class="mt-2 block text-sm">
                                    — {{ $block['data']['author'] }}
                                </cite>
                            @endif
                        </blockquote>

                    @elseif($block['type'] === 'code')
                        <pre class="p-4 my-6 rounded-lg overflow-x-auto">
                            <code class="text-sm">{{ $block['data']['content'] ?? '' }}</code>
                        </pre>
                    @endif
                @endforeach
            @endif

            {{-- Tags --}}
            @if($article->tags && count($article->tags) > 0)
                <div class="mt-8 pt-6 mx-auto w-full max-w-6xl">
                    <h3 class="text-sm font-semibold mb-3">Tags:</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($article->tags as $tag)
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium text-white bg-purple-600 rounded-full">
                                #{{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- SEO Meta (Hidden, for reference) --}}
            @if($article->seo_title || $article->meta_description)
                {{-- These are typically in head section, shown here for completeness --}}
                <div class="hidden">
                    <p>SEO Title: {{ $article->seo_title }}</p>
                    <p>Meta Description: {{ $article->meta_description }}</p>
                </div>
            @endif
        </article>
    </div>
</main>
