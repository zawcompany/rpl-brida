@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Naskah Saya</h1>
        <p class="text-sm text-gray-500 mt-1">Pantau status dan progres seluruh naskah yang Anda ajukan.</p>
    </div>
    <a href="{{ route('author.manuscripts.create') }}"
       class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajukan Naskah Baru
    </a>
</div>

@if (session('success'))
    <div role="status" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
        {{ session('success') }}
    </div>
@endif

<x-author.data-table
    :url="route('author.manuscripts.index')"
    :headers="['Judul Naskah', 'Bidang Keahlian', 'Tanggal Submit', 'Status', 'Aksi']"
    :rows="$rows"
    rows-view="roles.author.partials.manuscript-table-rows"
    :statuses="$statuses"
    :status="$initialStatus"
    placeholder="Cari judul atau kata kunci..."
    unit="naskah" />

@include('roles.author.partials.tracking-modal')
@endsection
