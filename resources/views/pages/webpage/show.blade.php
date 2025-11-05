@extends('layouts.app')

@section('title', $page->seo_title ?: $page->name)
@section('description', $page->meta_description)

@push('meta')
    @if($page->meta_keywords)
        <meta name="keywords" content="{{ implode(', ', $page->meta_keywords) }}">
    @endif
    @if($page->noindex)
        <meta name="robots" content="noindex{{ $page->nofollow ? ', nofollow' : '' }}">
    @endif
@endpush

@push('schemas')
    <script type="application/ld+json">
        {!! json_encode($page->toJsonLd(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
<div class="container py-12">
    <div class="max-w-4xl mx-auto">
        {{-- Page Header --}}
        <header class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-default-900 mb-4">
                {{ $page->name }}
            </h1>
            @if($page->excerpt)
                <p class="text-lg text-default-600">
                    {{ $page->excerpt }}
                </p>
            @endif
        </header>

        {{-- Page Content --}}
        <div class="prose prose-default max-w-none">
            @if($page->content)
                @foreach($page->content as $block)
                    @if($block['type'] === 'heading')
                        @php
                            $level = $block['data']['level'] ?? 'h2';
                            $content = $block['data']['content'] ?? '';
                        @endphp
                        <{{ $level }} class="font-bold text-default-900 mt-8 mb-4">
                            {!! $content !!}
                        </{{ $level }}>
                    @elseif($block['type'] === 'paragraph')
                        <div class="text-default-700 leading-relaxed mb-4">
                            {!! $block['data']['content'] ?? '' !!}
                        </div>
                    @elseif($block['type'] === 'list')
                        @php
                            $listType = $block['data']['style'] ?? 'unordered';
                            $items = $block['data']['items'] ?? [];
                        @endphp
                        @if($listType === 'ordered')
                            <ol class="list-decimal list-inside mb-4 text-default-700">
                                @foreach($items as $item)
                                    <li>{!! $item !!}</li>
                                @endforeach
                            </ol>
                        @else
                            <ul class="list-disc list-inside mb-4 text-default-700">
                                @foreach($items as $item)
                                    <li>{!! $item !!}</li>
                                @endforeach
                            </ul>
                        @endif
                    @elseif($block['type'] === 'image')
                        <figure class="my-6">
                            <img
                                src="{{ $block['data']['url'] ?? '' }}"
                                alt="{{ $block['data']['caption'] ?? '' }}"
                                class="w-full rounded-lg shadow-md">
                            @if(!empty($block['data']['caption']))
                                <figcaption class="text-sm text-default-500 text-center mt-2">
                                    {{ $block['data']['caption'] }}
                                </figcaption>
                            @endif
                        </figure>
                    @elseif($block['type'] === 'quote')
                        <blockquote class="border-l-4 border-primary-500 pl-4 py-2 my-6 italic text-default-600">
                            {!! $block['data']['content'] ?? '' !!}
                            @if(!empty($block['data']['caption']))
                                <cite class="block text-sm text-default-500 mt-2 not-italic">
                                    — {{ $block['data']['caption'] }}
                                </cite>
                            @endif
                        </blockquote>
                    @elseif($block['type'] === 'code')
                        <pre class="bg-default-100 rounded-lg p-4 overflow-x-auto my-4"><code class="text-sm">{{ $block['data']['code'] ?? '' }}</code></pre>
                    @endif
                @endforeach
            @else
                <div class="text-center py-12">
                    <p class="text-default-500">Konten belum tersedia.</p>
                </div>
            @endif
        </div>

        {{-- Page Footer / Meta --}}
        <footer class="mt-12 pt-6 border-t border-default-200">
            <div class="flex items-center justify-between text-sm text-default-500">
                <div>
                    Terakhir diperbarui: {{ $page->updated_at->format('d F Y') }}
                </div>
                <a href="{{ route('home') }}" class="text-primary-600 hover:text-primary-700 transition-colors">
                    ← Kembali ke Beranda
                </a>
            </div>
        </footer>
    </div>
</div>
@endsection
