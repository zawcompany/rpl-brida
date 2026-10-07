@extends('layouts.app')

@section('content')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <!-- Sisi Kiri: Teks -->
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Administrator</h1>
            <p class="text-gray-600 leading-relaxed">
                Anda memiliki kendali penuh atas seluruh aspek sistem. Kelola pengguna, atur hak akses, dan pantau keseluruhan aktivitas di dalam platform untuk memastikan proses berjalan aman, lancar, dan sesuai dengan standar yang ditetapkan.
            </p>
        </div>
        
        <!-- Sisi Kanan: Ilustrasi -->
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img 
                src="{{ asset('images/administrator.png') }}" 
                alt="Ilustrasi Administrator" 
                class="w-48 h-auto object-contain drop-shadow-md"
                onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Administrator+Illustration';"
            >
        </div>
    </div>
</div>

<!-- Konten Tambahan Dashboard Administrator -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Kelola Pengguna</h3>
        <p class="text-gray-500 text-sm mt-2">Tambah, perbarui, atau hapus akses pengguna dalam sistem.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Hak Akses</h3>
        <p class="text-gray-500 text-sm mt-2">Konfigurasi peran dan wewenang untuk masing-masing tipe akun.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Aktivitas Sistem</h3>
        <p class="text-gray-500 text-sm mt-2">Tinjau log dan metrik penggunaan platform secara real-time.</p>
    </div>
</div>
@endsection