@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Role & Hak Akses</h1>
    <p class="text-sm text-gray-500 mt-1">Ringkasan hak akses tiap role dan pembaruan role akun pengguna.</p>
</div>

{{-- Matriks hak akses --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    @foreach ($permissions as $role => $items)
    @php $label = \App\Models\User::ROLE_LABELS[$role]; $badge = (new \App\Models\User(['role' => $role]))->role_badge_class; @endphp
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <x-editor.badge :color="$badge">{{ $label }}</x-editor.badge>
            <span class="text-xs text-gray-500">{{ $counts[$label] ?? 0 }} pengguna</span>
        </div>
        <ul class="space-y-1.5">
            @foreach ($items as $item)
                <li class="flex items-start gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 mt-0.5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ $item }}
                </li>
            @endforeach
        </ul>
    </div>
    @endforeach
</div>

<x-admin.data-table
    :url="route('admin.roles.index')"
    :headers="['Nama Pengguna', 'Email', 'Role Aktif', 'Status Akun', 'Aksi']"
    :rows="$rows"
    rows-view="roles.admin.partials.role-table-rows"
    :roles="$roleOptions"
    placeholder="Cari nama atau email..."
    unit="pengguna" />

@include('roles.admin.partials.role-modal')
@endsection
