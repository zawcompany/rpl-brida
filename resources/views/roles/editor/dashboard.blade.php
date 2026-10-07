@extends('layouts.app')

@section('content')
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <!-- Sisi Kiri: Teks -->
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Editor</h1>
            <p class="text-gray-600 leading-relaxed">
                Sebagai Editor, Anda memegang peran krusial dalam menjaga kualitas publikasi. Kelola naskah yang masuk, tugaskan peninjau yang tepat, dan putuskan kelayakan suatu karya untuk memastikan hanya artikel terbaik yang terbit di direktori kami.
            </p>
        </div>
        
        <!-- Sisi Kanan: Ilustrasi -->
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img 
                src="{{ asset('images/editor.png') }}" 
                alt="Ilustrasi Editor" 
                class="w-48 h-auto object-contain drop-shadow-md"
                onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Editor+Illustration';"
            >
        </div>
    </div>
</div>

<!-- Konten Tambahan Dashboard Editor -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Naskah Baru</h3>
        <p class="text-gray-500 text-sm mt-2">Tinjau cepat naskah yang baru saja masuk dari Author.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Peninjauan</h3>
        <p class="text-gray-500 text-sm mt-2">Pantau progres evaluasi dari para Reviewer yang ditugaskan.</p>
    </div>

    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col items-start">
        <div class="p-3 bg-red-50 text-red-600 rounded-lg mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Keputusan</h3>
        <p class="text-gray-500 text-sm mt-2">Tentukan hasil akhir naskah (Terima, Revisi, atau Tolak).</p>
    </div>
</div>
@endsection