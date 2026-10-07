@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-xl font-bold text-gray-800">Dashboard Author</h1>
            <p class="text-gray-500 text-sm mt-1">Pantau status pengajuan naskah ilmiah dan riwayat revisi Anda.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Status Naskah Terakhir</h2>
            <div class="border rounded-lg p-4 flex justify-between items-center">
                <div>
                    <h3 class="font-medium text-gray-800">Analisis Kinerja Infrastruktur Cloud BRIDA</h3>
                    <p class="text-xs text-gray-500 mt-1">Diajukan pada: 10 Oktober 2026</p>
                </div>
                <span class="px-3 py-1 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">Perlu Revisi</span>
            </div>
        </div>
    </div>
@endsection