@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Hasil Review & Revisi</h1>
    <p class="text-sm text-gray-500 mt-1">Naskah yang memerlukan perbaikan dari Anda. Setelah revisi dikirim, naskah kembali ke editor untuk diputuskan.</p>
</div>

<x-author.data-table
    :url="route('author.revisions.index')"
    :headers="['Judul Naskah', 'Tanggal Catatan', 'Catatan Editor/Reviewer', 'Status', 'Aksi']"
    :rows="$rows"
    rows-view="roles.author.partials.revision-table-rows"
    placeholder="Cari judul naskah..."
    unit="naskah" />

@include('roles.author.partials.revision-modal')
@endsection
