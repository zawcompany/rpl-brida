@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Naskah Selesai Direview</h1>
    <p class="text-sm text-gray-500 mt-1">Riwayat review yang sudah Anda kirim. Hasil masih dapat diperbaiki selama editor belum memutuskan.</p>
</div>

<x-author.data-table
    :url="route('reviewer.manuscripts-selesai')"
    :headers="['Judul Naskah', 'Tanggal Penugasan', 'Batas Waktu', 'Status Review', 'Aksi']"
    :rows="$rows"
    rows-view="roles.reviewer.partials.assignment-table-rows"
    placeholder="Cari judul naskah..."
    unit="review" />

@include('roles.reviewer.partials.review-modal')
@endsection
