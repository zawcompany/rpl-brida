<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMPIL - Sistem Informasi Manajemen Publikasi Ilmiah BRIDA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 min-h-screen flex flex-col">

    <!-- HEADER / NAVBAR TEMPLATE UMUM -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- SISI KIRI: LOGO BRIDA -->
            <div class="flex-shrink-0 flex items-center w-64">
                <a href="{{ url('/') }}" class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo-brida.png') }}" alt="Logo BRIDA" class="h-10 w-auto">
                </a>
            </div>

            <!-- SISI TENGAH: PENCARIAN NASKAH ATAU JURNAL -->
            <div class="flex-1 max-w-2xl">
                <form action="#" method="GET" class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input 
                        type="search" 
                        name="q" 
                        placeholder="Cari pengguna atau naskah..." 
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border border-transparent rounded-full text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all shadow-inner"
                    >
                </form>
            </div>

            <!-- SISI KANAN: MASUK / DAFTAR -->
            <div class="flex-shrink-0 flex items-center space-x-3">
                @auth
                    <!-- Jika Pengguna Sudah Login -->
                    <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors shadow-sm">
                        Dashboard
                    </a>
                @else
                    <!-- Jika Pengguna Belum Login (Guest/Reader) -->
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:text-red-600 hover:bg-gray-50 transition-colors">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors shadow-sm">
                        Daftar
                    </a>
                @endauth
            </div>

        </div>
    </header>

    <!-- AREA KONTEN UTAMA (KERANGKA UNTUK TIM) -->
    <main class="flex-1">
        
        <!-- SECTION HERO / BANNER -->
        <section class="py-12 bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Sistem Informasi Manajemen Publikasi Ilmiah
                </h1>
                <p class="mt-3 max-w-2xl mx-auto text-base text-gray-500">
                    Badan Riset dan Inovasi Daerah (BRIDA) Kota Makassar
                </p>
            </div>
        </section>

        <!-- CONTAINER UNTUK DIISI OLEH ANGGOTA TIM -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            
            <!-- PLACEHOLDER KONTEN LANDING PAGE -->
            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-12 text-center bg-white shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-800">Area Konten Landing Page</h3>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    Bagian ini disiapkan sebagai tempat untuk tim Anda menambahkan daftar jurnal publikasi, artikel unggulan, atau statistik BRIDA.
                </p>
            </div>

        </div>

    </main>

    <!-- FOOTER SEDERHANA -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-500">
        &copy; {{ date('Y') }} BRIDA Kota Makassar. All rights reserved.
    </footer>

</body>
</html>