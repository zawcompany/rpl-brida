@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Naskah Ditugaskan</h1>
    <p class="text-sm text-gray-500 mt-1">Naskah yang ditugaskan editor kepada Anda. Terima penugasan, lalu berikan penilaian dan rekomendasi.</p>
</div>

<x-author.data-table
    :url="route('reviewer.manuscripts.index')"
    :headers="['Judul Naskah', 'Tanggal Penugasan', 'Batas Waktu', 'Status Review', 'Aksi']"
    :rows="$rows"
    rows-view="roles.reviewer.partials.assignment-table-rows"
    :statuses="$statuses"
    placeholder="Cari judul naskah..."
    unit="penugasan" />

@include('roles.reviewer.partials.review-modal')
@endsection
