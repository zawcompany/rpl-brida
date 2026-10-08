@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Peninjauan Naskah</h1>
    <p class="text-sm text-gray-500 mt-1">Pantau progres review, kirim pengingat, atau ganti reviewer yang menolak / terlambat.</p>
</div>

<x-editor.data-table
    :url="route('editor.reviews.index')"
    :headers="['Naskah', 'Penulis', 'Tanggal', 'Status Review', 'Aksi']"
    :rows="$rows"
    rows-view="roles.editor.partials.review-table-rows"
    filter="date"
    placeholder="Cari judul atau penulis..."
    unit="naskah" />

@include('roles.editor.partials.review-modal')
@endsection
