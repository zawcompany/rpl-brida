@extends('layouts.app')

@section('content')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <!-- Sisi Kiri: Teks -->
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Author</h1>
            <p class="text-gray-600 leading-relaxed">
                Ini adalah ruang kerja Anda untuk mengajukan karya ilmiah terbaik. Lacak status penerimaan naskah Anda, perbaiki berdasarkan umpan balik berharga dari tim peninjau kami, dan melangkahlah bersama menuju publikasi profesional.
            </p>
        </div>
        
        <!-- Sisi Kanan: Ilustrasi -->
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img 
                src="{{ asset('images/author.png') }}" 
                alt="Ilustrasi Author" 
                class="w-48 h-auto object-contain drop-shadow-md"
                onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Author+Illustration';"
            >
        </div>
    </div>
</div>

<!-- Konten Tambahan Dashboard Author -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Ajukan Naskah</h3>
        <p class="text-gray-500 text-sm mt-2">Mulai proses submisi untuk menerbitkan manuskrip baru Anda.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Naskah Saya</h3>
        <p class="text-gray-500 text-sm mt-2">Lihat semua naskah yang telah diajukan beserta status terkininya.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Revisi & Diskusi</h3>
        <p class="text-gray-500 text-sm mt-2">Berinteraksi dengan editor terkait perbaikan naskah Anda.</p>
    </div>
</div>
@endsection