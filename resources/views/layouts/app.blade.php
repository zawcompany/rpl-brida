<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIMPIL - BRIDA Kota Makassar' }}</title>
    <!-- Tambahkan CSS/Tailwind jika ada -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR (Kiri - Sesuai Gambar) -->
        <aside class="w-64 bg-[#F9F9F9] border-r border-gray-200 flex flex-col justify-between">
            <div>
                <!-- Logo Instansi -->
                <div class="p-6 flex items-center justify-center">
                    <img src="{{ asset('images/logo-brida.png') }}" alt="Logo BRIDA" class="h-10">
                </div>

                <!-- Navigasi Menu -->
                <nav class="px-4 space-y-1 mt-2 text-sm text-gray-600">
                    <!-- Tombol Dashboard Aktif -->
                    <a href="#" class="flex items-center gap-3 px-4 py-2.5 bg-[#E87B79] text-white rounded-lg font-medium">
                        <span>Dashboard</span>
                    </a>
                    
                    <!-- Kategori Pengguna -->
                    <div class="pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase px-4">Pengguna</div>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Menu 1</a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Menu 2</a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Menu 3</a>

                    <!-- Kategori Lainnya -->
                    <div class="pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase px-4">Lainnya</div>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Menu 4</a>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Menu 5</a>

                    <!-- Pengaturan Profil -->
                    <div class="pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase px-4">Lainnya</div>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-200 rounded-lg">Profil</a>
                </nav>
            </div>

            <!-- Tombol Keluar di Bagian Bawah Sidebar -->
            <div class="p-4 border-t border-gray-200">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg font-medium">
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- KONTEN UTAMA (Kanan) -->
        <div class="flex-1 flex flex-col h-screen overflow-y-auto">
            
            <!-- TOPBAR (Atas - Sesuai Gambar) -->
            <header class="bg-white h-16 border-b border-gray-200 flex items-center justify-between px-8">
                <!-- Ikon Toggle Garis Tiga / Menu -->
                <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- Bagian Kanan (Notifikasi & Profil User) -->
                <div class="flex items-center gap-6">
                    <!-- Ikon Lonceng Notifikasi -->
                    <button class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </button>

                    <!-- Profil Pengguna & Role -->
                    <div class="flex items-center gap-3 border-l pl-6 border-gray-200">
                        <div class="w-9 h-9 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            {{-- Placeholderinisial nama atau foto --}}
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="text-xs text-gray-500 capitalize">{{ Auth::user()->role ?? 'Role' }}</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- AREA KONTEN UTAMA (Tempat isi halaman ditampikan) -->
            <main class="p-8">
                @yield('content')
            </main>

        </div>
    </div>

</body>
</html>