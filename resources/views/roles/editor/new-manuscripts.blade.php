@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Naskah Baru</h1>
    <p class="text-sm text-gray-500 mt-1">Daftar naskah yang masuk dan menunggu pemeriksaan administrasi awal.</p>
</div>

<x-editor.data-table
    :url="route('editor.manuscripts.new')"
    :headers="['Naskah', 'Penulis', 'Tanggal', 'Aksi']"
    :rows="$rows"
    rows-view="roles.editor.partials.manuscript-table-rows"
    filter="date"
    placeholder="Cari judul atau penulis..."
    unit="naskah" />

@include('roles.editor.partials.manuscript-modal')
@endsection
