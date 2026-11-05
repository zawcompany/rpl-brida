@extends('layouts.app', ['title' => $journal->name . ' — Kelola Jurnal'])

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <a href="{{ route('admin.journals.index') }}" class="text-sm text-gray-500 hover:text-red-600">← Semua jurnal</a>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $journal->name }}</h1>
    <p class="text-sm text-gray-500 mt-1">Halaman publik: <a class="text-red-600 hover:underline" href="{{ route('journals.show', ['journal' => $journal, 'dari' => 'dashboard']) }}">{{ route('journals.show', $journal, false) }}</a></p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-800">Data Jurnal</h2></div>
    <form method="POST" action="{{ route('admin.journals.update', $journal) }}" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf @method('PUT')
        <div class="md:col-span-2">
            <label for="name" class="block text-xs font-semibold text-gray-600 mb-1.5">Nama jurnal</label>
            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $journal->name) }}" class="{{ $input }}">
        </div>
        <div>
            <label for="abbreviation" class="block text-xs font-semibold text-gray-600 mb-1.5">Singkatan</label>
            <input id="abbreviation" name="abbreviation" type="text" maxlength="30" value="{{ old('abbreviation', $journal->abbreviation) }}" class="{{ $input }}">
        </div>
        <div class="md:col-span-2">
            <label for="field" class="block text-xs font-semibold text-gray-600 mb-1.5">Bidang</label>
            <input id="field" name="field" type="text" maxlength="120" value="{{ old('field', $journal->field) }}" placeholder="mis. Kesehatan Masyarakat" class="{{ $input }}">
        </div>
        <div class="flex items-start gap-3">
            <x-public.journal-cover :journal="$journal" class="h-24 w-[4.5rem] flex-shrink-0 rounded-lg" />
            <div class="min-w-0 flex-1">
                <label for="cover" class="block text-xs font-semibold text-gray-600 mb-1.5">Cover jurnal</label>
                <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WebP, maks. 2 MB. Pilih berkas baru untuk mengganti.</p>
                @if ($journal->cover_image)
                    <label class="mt-2 flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" name="remove_cover" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        Hapus cover (pakai sampul gradasi bawaan)
                    </label>
                @endif
            </div>
        </div>
        <div class="md:col-span-3">
            <label for="description" class="block text-xs font-semibold text-gray-600 mb-1.5">Deskripsi</label>
            <textarea id="description" name="description" rows="2" maxlength="2000" class="{{ $input }}">{{ old('description', $journal->description) }}</textarea>
        </div>
        <label class="md:col-span-3 flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $journal->is_active)) class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Jurnal aktif (menerima anggota dan tampil di daftar jurnal)
        </label>
        <div class="md:col-span-3"><button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Simpan</button></div>
    </form>
</section>

@include('journals.partials.manage')
@endsection
