@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Keputusan Editorial</h1>
    <p class="text-sm text-gray-500 mt-1">Naskah yang telah selesai direview dan menunggu keputusan akhir editor.</p>
</div>

<x-editor.data-table
    :url="route('editor.decisions.index')"
    :headers="['Naskah', 'Penulis', 'Tanggal', 'Rekomendasi Reviewer', 'Aksi']"
    :rows="$rows"
    rows-view="roles.editor.partials.decision-table-rows"
    filter="date"
    placeholder="Cari judul atau penulis..."
    unit="naskah" />

@include('roles.editor.partials.decision-modal')
@endsection
