<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SIMPIL BRIDA Kota Makassar' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased text-gray-800 min-h-screen flex flex-col">

    @php
        $nav = [
            ['Beranda', route('home'), request()->routeIs('home')],
            ['Artikel', route('reader.index'), request()->routeIs('reader.*')],
            ['Arsip', route('archives.index'), request()->routeIs('archives.*')],
            ['Panduan Penulis', route('home') . '#panduan', false],
        ];
    @endphp

    {{-- HEADER PUBLIK --}}
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="h-20 flex items-center justify-between gap-4">

                {{-- Nama SIMPIL --}}
                <a href="{{ route('home') }}" class="flex-shrink-0">
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">SIMPIL</h1>
                    <p class="hidden sm:block text-xs text-gray-500">Sistem Informasi Manajemen Publikasi Ilmiah</p>
                </a>

                {{-- Navigasi (desktop) --}}
                <nav class="hidden lg:flex items-center gap-1" aria-label="Navigasi utama">
                    @foreach ($nav as [$label, $url, $active])
                        <a href="{{ $url }}"
                           class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-red-50 text-red-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">{{ $label }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Pencarian artikel --}}
                    <form action="{{ route('reader.index') }}" method="GET" class="relative hidden md:block">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari artikel..."
                               class="w-44 xl:w-60 pl-9 pr-3 py-2 bg-gray-100 border border-transparent rounded-full text-sm placeholder-gray-400 focus:outline-none focus:bg-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all">
                    </form>

                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors shadow-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-block px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:text-red-600 hover:bg-gray-50 transition-colors">Masuk</a>
                        <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors shadow-sm">Daftar</a>
                    @endauth

                    <a href="{{ route('home') }}" class="hidden xl:block">
                        <img src="{{ asset('images/logo-brida.png') }}" alt="BRIDA Kota Makassar" class="h-10 w-auto">
                    </a>

                    {{-- Tombol menu (mobile) --}}
                    <button type="button" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Menu mobile --}}
        <div x-show="open" x-cloak class="lg:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
            <form action="{{ route('reader.index') }}" method="GET" class="mb-2">
                <input type="search" name="q" maxlength="100" placeholder="Cari artikel..."
                       class="w-full px-4 py-2 bg-gray-100 rounded-full text-sm focus:outline-none focus:ring-1 focus:ring-red-500">
            </form>
            @foreach ($nav as [$label, $url, $active])
                <a href="{{ $url }}" class="block px-3 py-2 rounded-lg text-sm font-medium {{ $active ? 'bg-red-50 text-red-700' : 'text-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
            @endforeach
            @guest
                <a href="{{ route('login') }}" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Masuk</a>
            @endguest
        </div>
    </header>

    {{-- ISI HALAMAN --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- FOOTER PUBLIK --}}
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-500">
            <div class="text-center md:text-left">
                <p class="font-semibold text-gray-800">SIMPIL — BRIDA Kota Makassar</p>
                <p>Badan Riset dan Inovasi Daerah Kota Makassar</p>
            </div>
            <div class="flex items-center gap-5">
                <a href="{{ route('reader.index') }}" class="hover:text-red-600">Artikel</a>
                <a href="{{ route('archives.index') }}" class="hover:text-red-600">Arsip</a>
                <a href="{{ route('home') }}#panduan" class="hover:text-red-600">Panduan Penulis</a>
            </div>
            <p>&copy; {{ date('Y') }} BRIDA Kota Makassar</p>
        </div>
    </footer>

    @stack('scripts')

</body>
</html>
