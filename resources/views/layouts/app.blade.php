<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Ogitu E-commerce - @yield('title', 'Beranda')</title>

    <!-- Font Umum: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" crossorigin src="{{ asset('assets/js/theme-163fefad.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/theme-dc2d9d4e.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Slot untuk CSS Tambahan/Spesifik Halaman -->
    @yield('styles')
    @stack('meta')
    @livewireStyles
</head>
<body>
    <!-- Preloader -->
    {{-- <div id="preloader"
         class="fixed inset-0 z-70 bg-default-50 transition-all visible opacity-100 h-screen w-screen flex items-center justify-center">
        <div class="animate-spin inline-block w-10 h-10 border-4 border-t-transparent border-primary rounded-full"
             role="status" aria-label="loading">
            <span class="sr-only">Memuat...</span>
        </div>
    </div> --}}
    @livewire('ecommerce.navbar')
    @include('layouts.partials.breadscrumb')
    @yield('content')
    <!-- Slot untuk JavaScript Tambahan/Spesifik Halaman -->
    @include('layouts.partials.footer')
    @livewireScripts
    @yield('scripts')
</body>
</html>
