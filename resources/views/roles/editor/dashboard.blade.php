@extends('layouts.app')

@section('content')
<div class="space-y-8">

    <!-- Card Banner Header -->
    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-gray-400 text-sm">Selamat datang,</p>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Editor</h1>
            <p class="text-gray-400 text-sm mt-1 max-w-lg">
                Kelola pemeriksaan naskah masuk, penugasan penelaah (reviewer), dan keputusan penerbitan artikel.
            </p>
        </div>
        <!-- Ilustrasi / Grafis Statis -->
        <div class="hidden lg:block">
            <div class="w-32 h-24 bg-red-50 rounded-xl flex items-center justify-center border border-red-100">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Grid 4 Card Ringkasan Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Naskah baru -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-gray-200 mb-4"></div>
            <p class="text-sm font-medium text-gray-700">Naskah baru</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">10</p>
            <p class="text-xs text-emerald-600 font-semibold mt-3 flex items-center">
                <span>↑ 12%</span> <span class="text-gray-400 font-normal ml-1">dari bulan lalu</span>
            </p>
        </div>

        <!-- Card 2: Sedang ditinjau -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-gray-200 mb-4"></div>
            <p class="text-sm font-medium text-gray-700">Sedang ditinjau</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">10</p>
            <p class="text-xs text-emerald-600 font-semibold mt-3 flex items-center">
                <span>↑ 12%</span> <span class="text-gray-400 font-normal ml-1">dari bulan lalu</span>
            </p>
        </div>

        <!-- Card 3: Menunggu keputusan -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-gray-200 mb-4"></div>
            <p class="text-sm font-medium text-gray-700">Menunggu keputusan</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">10</p>
            <p class="text-xs text-emerald-600 font-semibold mt-3 flex items-center">
                <span>↑ 12%</span> <span class="text-gray-400 font-normal ml-1">dari bulan lalu</span>
            </p>
        </div>

        <!-- Card 4: Menunggu publikasi -->
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="w-10 h-10 rounded-full bg-gray-200 mb-4"></div>
            <p class="text-sm font-medium text-gray-700">Menunggu publikasi</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">10</p>
            <p class="text-xs text-rose-500 font-semibold mt-3 flex items-center">
                <span>↓ 12%</span> <span class="text-gray-400 font-normal ml-1">dari bulan lalu</span>
            </p>
        </div>
    </div>

    <!-- Tabel Aktivitas Terbaru -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-900">Aktivitas Terbaru</h2>
            <a href="#" class="text-sm font-semibold text-red-600 hover:text-red-700 flex items-center">
                Lihat Semua <span class="ml-1">→</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4 rounded-l-lg">Naskah</th>
                        <th class="py-3 px-4">Penulis</th>
                        <th class="py-3 px-4">Aktivitas</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 rounded-r-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-4 px-4 font-medium text-gray-900 max-w-xs">
                            Analisis Potensi Ekonomi Kreatif Sulawesi Selatan
                        </td>
                        <td class="py-4 px-4 text-gray-600">Andi Pratama</td>
                        <td class="py-4 px-4">
                            <span class="px-3 py-1 text-xs font-medium bg-emerald-50 text-emerald-700 rounded-full">
                                Review Selesai
                            </span>
                        </td>
                        <td class="py-4 px-4 text-gray-500">23 September 2026</td>
                        <td class="py-4 px-4">
                            <span class="px-3 py-1 text-xs font-medium bg-amber-50 text-amber-700 rounded-full">
                                Menunggu keputusan
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <a href="#" class="text-xs font-semibold text-red-600 hover:underline">Lihat</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection