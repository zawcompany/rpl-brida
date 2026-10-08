@extends('layouts.app')

@section('content')
{{-- WELCOME BANNER --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
    <div class="flex flex-col md:flex-row items-center justify-between p-6 md:p-8">
        <div class="md:w-2/3">
            <p class="text-gray-500 font-medium mb-1">Selamat datang,</p>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ Auth::user()->name }}</h1>
            <p class="text-gray-600 leading-relaxed text-sm">
                Anda memiliki kendali atas pengguna dan hak akses sistem. Kelola akun, atur role, dan pantau aktivitas platform agar proses berjalan aman dan sesuai standar.
            </p>
        </div>
        <div class="md:w-1/3 mt-6 md:mt-0 flex justify-end">
            <img src="{{ asset('images/administrator.png') }}" alt="Ilustrasi Administrator"
                 class="w-48 h-auto object-contain drop-shadow-md"
                 onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Administrator+Illustration';">
        </div>
    </div>
</div>

{{-- STAT WIDGETS --}}
@php
    $card = 'bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3 transition hover:shadow-md hover:-translate-y-0.5';
    $roleColors = ['Author' => 'bg-gray-100 text-gray-700', 'Editor' => 'bg-purple-100 text-purple-700', 'Reviewer' => 'bg-blue-100 text-blue-700', 'Admin' => 'bg-red-100 text-red-700'];
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <a href="{{ route('admin.users.index') }}" class="{{ $card }}">
        <span class="text-sm font-medium text-gray-500">Total Pengguna Terdaftar</span>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
        <span class="text-xs text-gray-400">Kelola pengguna →</span>
    </a>

    <a href="{{ route('admin.roles.index') }}" class="{{ $card }}">
        <span class="text-sm font-medium text-gray-500">Ringkasan Per-Role</span>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($stats['roles'] as $label => $count)
                <x-editor.badge :color="$roleColors[$label] ?? 'bg-gray-100 text-gray-700'">{{ $label }}: {{ $count }}</x-editor.badge>
            @endforeach
        </div>
        <span class="text-xs text-gray-400">Role & hak akses →</span>
    </a>

    <div class="{{ $card }}">
        <span class="text-sm font-medium text-gray-500">Total Naskah Terdaftar</span>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_manuscripts'] }}</p>
        <span class="text-xs text-gray-400">Seluruh status</span>
    </div>

    <div class="{{ $card }}">
        <span class="text-sm font-medium text-gray-500">Pengguna Baru (30 Hari)</span>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['new_users'] }}</p>
        <span class="text-xs text-gray-400">Sejak {{ now()->subDays(30)->format('d M Y') }}</span>
    </div>
</div>

{{-- AUDIT LOG TERBARU --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="text-base font-bold text-gray-900">Aktivitas Sistem / Audit Log Terbaru</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Waktu', 'Pelaku', 'Aktivitas', 'Alamat IP'] as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse ($logs as $log)
                <tr class="hover:bg-gray-50/60 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap" title="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $log->actor?->name ?? 'Sistem' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-800">{{ $log->description }}</td>
                    <td class="px-6 py-4 text-xs text-gray-400 whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                    @include('roles.editor.partials.empty-row', ['colspan' => 4, 'message' => 'Belum ada aktivitas tercatat.'])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
