@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Direktori Reviewer</h1>
    <p class="text-sm text-gray-500 mt-1">Daftar reviewer beserta bidang keahlian dan beban naskah yang sedang ditangani.</p>
</div>

<x-editor.data-table
    :url="route('editor.reviewers.index')"
    :headers="['Nama Reviewer', 'Email', 'Bidang Keahlian', 'Naskah Ditangani', 'Status', 'Aksi']"
    :rows="$rows"
    rows-view="roles.editor.partials.reviewer-table-rows"
    filter="field"
    :fields="$fields"
    placeholder="Cari nama atau email..."
    unit="reviewer" />

@include('roles.editor.partials.reviewer-modal')
@endsection
