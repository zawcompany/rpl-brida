@extends('layouts.app')

@section('content')

{{-- ============================================================ --}}
{{-- WELCOME BANNER --}}
{{-- ============================================================ --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ Auth::user()->name }}</h1>
            <p class="text-gray-500 leading-relaxed text-sm">
                Kelola naskah yang masuk, tugaskan peninjau yang tepat, dan putuskan kelayakan suatu karya untuk memastikan hanya artikel terbaik yang terbit.
            </p>
        </div>
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img src="{{ asset('images/editor.png') }}" alt="Ilustrasi Editor"
                 class="w-44 h-auto object-contain drop-shadow-md"
                 onerror="this.onerror=null;this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Editor';">
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- STAT WIDGETS (4 Kartu) --}}
{{-- ============================================================ --}}
@php
    $iconMap = [
        'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
        'eye'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    ];
    $colorMap = [
        'blue'   => ['bg' => 'bg-blue-50',   'text' => 'text-blue-600',   'badge_up' => 'bg-green-100 text-green-700', 'badge_dn' => 'bg-red-100 text-red-700'],
        'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'badge_up' => 'bg-green-100 text-green-700', 'badge_dn' => 'bg-red-100 text-red-700'],
        'yellow' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'badge_up' => 'bg-green-100 text-green-700', 'badge_dn' => 'bg-red-100 text-red-700'],
        'green'  => ['bg' => 'bg-green-50',  'text' => 'text-green-600',  'badge_up' => 'bg-green-100 text-green-700', 'badge_dn' => 'bg-red-100 text-red-700'],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    @foreach ($stats as $stat)
    @php
        $c = $colorMap[$stat['color']];
        $icon = $iconMap[$stat['icon']];
        $badgeClass = $stat['change_direction'] === 'up' ? $c['badge_up'] : $c['badge_dn'];
        $arrow = $stat['change_direction'] === 'up' ? '↑' : '↓';
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</span>
            <div class="{{ $c['bg'] }} {{ $c['text'] }} p-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $icon !!}
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stat['count'] }}</p>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                {{ $arrow }} {{ $stat['change_percent'] }}%
            </span>
            <span class="text-xs text-gray-400">vs bulan lalu</span>
        </div>
    </div>
    @endforeach
</div>

{{-- ============================================================ --}}
{{-- TABEL AKTIVITAS TERBARU --}}
{{-- ============================================================ --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800">Aktivitas Terbaru</h2>
        <a href="{{ route('editor.manuscripts.new') }}"
           class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
            Lihat Semua →
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Naskah</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penulis</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aktivitas</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse ($recentActivity as $manuscript)
                <tr class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4 max-w-[220px]">
                        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $manuscript->title }}">
                            {{ $manuscript->title }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $manuscript->researchField?->name ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $manuscript->author?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @switch($manuscript->status)
                            @case('pending') Naskah masuk @break
                            @case('pemeriksaan_awal') Pemeriksaan awal @break
                            @case('ditinjau') Ditugaskan ke reviewer @break
                            @case('menunggu_keputusan') Review selesai @break
                            @case('revisi') Diminta revisi @break
                            @case('disetujui') Disetujui editor @break
                            @case('ditolak') Ditolak @break
                            @case('diterbitkan') Diterbitkan @break
                            @default {{ $manuscript->status }}
                        @endswitch
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                        {{ $manuscript->updated_at->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4">
                        @include('roles.editor.partials.status-badge', ['status' => $manuscript->status, 'label' => $manuscript->status_label])
                    </td>
                    <td class="px-6 py-4">
                        @if(in_array($manuscript->status, ['pending', 'pemeriksaan_awal']))
                        <button type="button"
                                onclick="openEditorModal('manuscript', {{ $manuscript->id }})"
                                class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline transition-colors">
                            Lihat Detail
                        </button>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">
                        Belum ada aktivitas naskah.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Detail Naskah (Reusable) --}}
@include('roles.editor.partials.manuscript-modal')

@endsection
