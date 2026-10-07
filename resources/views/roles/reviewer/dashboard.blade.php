@extends('layouts.app')

@section('content')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <!-- Sisi Kiri: Teks -->
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Reviewer</h1>
            <p class="text-gray-600 leading-relaxed">
                Kepakaran dan dedikasi Anda adalah fondasi utama kualitas publikasi ini. Silakan evaluasi naskah yang ditugaskan secara teliti, berikan kritik yang membangun, dan rekomendasikan kelayakan publikasinya untuk membantu pengembangan keilmuan.
            </p>
        </div>
        
        <!-- Sisi Kanan: Ilustrasi -->
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img 
                src="{{ asset('images/reviewer.png') }}" 
                alt="Ilustrasi Reviewer" 
                class="w-48 h-auto object-contain drop-shadow-md"
                onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Reviewer+Illustration';"
            >
        </div>
    </div>
</div>

<!-- Konten Tambahan Dashboard Reviewer -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Naskah Ditugaskan</h3>
        <p class="text-gray-500 text-sm mt-2">Akses daftar naskah yang menanti penelaahan (review) dari Anda beserta tenggat waktunya.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Profil Saya</h3>
        <p class="text-gray-500 text-sm mt-2">Kelola informasi kepakaran, minat penelitian, dan data diri Anda untuk kesesuaian penugasan.</p>
    </div>
</div>
@endsection