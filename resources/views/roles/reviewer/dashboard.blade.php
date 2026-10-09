@extends('layouts.app')

@section('content')
@php
    $icons = [
        'doc'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    ];
    $colors = [
        'blue'   => ['bg' => 'bg-blue-50',   'text' => 'text-blue-600'],
        'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
        'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-600'],
        'green'  => ['bg' => 'bg-green-50',  'text' => 'text-green-600'],
    ];
@endphp

{{-- WELCOME BANNER --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ Auth::user()->name }}</h1>
            <p class="text-gray-600 leading-relaxed text-sm">
                Silakan lakukan penelaahan terhadap naskah yang ditugaskan secara objektif dan teliti. Berikan penilaian, komentar,
                serta rekomendasi yang konstruktif untuk membantu Editor dalam menentukan kelayakan naskah.
            </p>
        </div>
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img src="{{ asset('images/reviewer.png') }}" alt="Ilustrasi Reviewer"
                 class="w-48 h-auto object-contain drop-shadow-md"
                 onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Reviewer+Illustration';">
        </div>
    </div>
</div>

{{-- STAT WIDGETS --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    @foreach ($stats as $stat)
    @php $c = $colors[$stat['color']]; $needsAction = $stat['warning'] && $stat['count'] > 0; @endphp
    <a href="{{ $stat['url'] }}"
       class="bg-white rounded-xl border shadow-sm p-5 flex flex-col gap-3 transition hover:shadow-md hover:-translate-y-0.5 {{ $needsAction ? 'border-orange-300 ring-1 ring-orange-200' : 'border-gray-200' }}">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</span>
            <div class="{{ $c['bg'] }} {{ $c['text'] }} p-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $icons[$stat['icon']] !!}</svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stat['count'] }}</p>
        @if ($stat['warning'])
            <x-editor.badge :color="$needsAction ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-500'" class="self-start">
                {{ $needsAction ? 'Segera selesaikan' : 'Tidak ada yang mendesak' }}
            </x-editor.badge>
        @else
            <span class="text-xs text-gray-400">Lihat daftar →</span>
        @endif
    </a>
    @endforeach
</div>

{{-- AKTIVITAS / RIWAYAT REVIEW TERBARU --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="text-base font-bold text-gray-900">Aktivitas / Riwayat Review Terbaru</h2>
        <a href="{{ route('reviewer.manuscripts.index') }}" class="text-xs font-medium text-red-600 hover:text-red-700">Lihat semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Judul Naskah', 'Tanggal Penugasan', 'Batas Waktu', 'Status Review', 'Aksi'] as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @include('roles.reviewer.partials.assignment-table-rows', ['rows' => $recent])
            </tbody>
        </table>
    </div>
</div>

@include('roles.reviewer.partials.review-modal', ['reload' => true])
@endsection
