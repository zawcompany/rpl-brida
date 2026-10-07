<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SIMPIL BRIDA Kota Makassar' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800" 
      x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR (Sisi Kiri) -->
        <aside 
            class="bg-white border-r border-gray-200 flex flex-col transition-all duration-300 ease-in-out select-none flex-shrink-0 z-20"
            :class="sidebarOpen ? 'w-64' : 'w-20'"
        >
            <!-- Header Sidebar & Logo -->
            <div class="h-20 flex items-center justify-center px-6 border-b border-gray-100 overflow-hidden">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center">
                    
                    <!-- 1. Logo Lengkap (Tampil HANYA saat sidebar TERBUKA / sidebarOpen = true) -->
                    <img x-show="sidebarOpen" 
                        src="{{ asset('images/logo-brida.png') }}" 
                        alt="Logo BRIDA Makassar" 
                        class="h-10 w-auto transition-all duration-300"
                        onerror="this.onerror=null; this.src='https://placehold.co/180x40/E53935/ffffff?text=BRIDA+MAKASSAR';">

                    <!-- 2. Pure Logo / Icon Saja (Tampil HANYA saat sidebar MINIMIZE / sidebarOpen = false) -->
                    <img x-show="!sidebarOpen" 
                        src="{{ asset('images/logo-brida-icon.png') }}" 
                        alt="Icon BRIDA" 
                        class="h-10 w-10 object-contain transition-all duration-300"
                        onerror="this.onerror=null; this.src='https://placehold.co/40x40/E53935/ffffff?text=B';">

                </a>
            </div>

            <!-- Navigasi Utama -->
            <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-6">
                <!-- Menu Dashboard Utama -->
                <div>
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center space-x-3 px-3.5 py-2.5 rounded-lg bg-red-600 text-white font-medium transition-colors shadow-sm"
                       :class="!sidebarOpen && 'justify-center px-0'"
                       title="Dashboard">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 00-1 1m-6 0h6"/>
                        </svg>
                        <span x-show="sidebarOpen" class="truncate">Dashboard</span>
                    </a>
                </div>

                <!-- Partial Menu Berdasarkan Role -->
                @include('layouts.partials.sidebar-menu')

                <!-- Logout -->
                <div class="pt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" 
                                class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors text-left"
                                :class="!sidebarOpen && 'justify-center px-0'"
                                title="Keluar">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span x-show="sidebarOpen" class="truncate">Keluar</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- AREA UTAMA (Sisi Kanan) -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 z-10">
                <div class="flex items-center space-x-4">
                    <button 
                        @click="sidebarOpen = !sidebarOpen" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-colors"
                        aria-label="Toggle Sidebar"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Profil Singkat & Notifikasi -->
                <div class="flex items-center space-x-6">
                    <button class="p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </button>

                    <div class="h-8 w-px bg-gray-200"></div>

                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-semibold overflow-hidden">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-semibold text-gray-800 leading-tight">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="text-xs text-gray-500 capitalize leading-tight mt-0.5">{{ Auth::user()->role ?? 'Role' }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- KONTEN DINAMIS HALAMAN -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-8 bg-gray-50">
                @yield('content')
            </main>
        </div>

    </div>

</body>
</html>