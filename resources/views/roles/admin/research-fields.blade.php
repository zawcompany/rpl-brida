@extends('layouts.app')

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Bidang Keahlian</h1>
    <p class="text-sm text-gray-500 mt-1">Daftar bidang yang dipilih Reviewer di profilnya dan dipakai pada form naskah serta pencarian artikel.</p>
</div>

@if (session('success'))
    <div role="status" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ $errors->first() }}</div>
@endif

{{-- TAMBAH --}}
<form method="POST" action="{{ route('admin.fields.store') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6 flex flex-col sm:flex-row gap-3 sm:items-end">
    @csrf
    <div class="flex-1">
        <label for="new-field" class="block text-sm font-semibold text-gray-700 mb-1.5">Tambah Bidang Baru</label>
        <input id="new-field" name="name" type="text" maxlength="120" required value="{{ old('name') }}" placeholder="Mis. Rekayasa Perangkat Lunak" class="{{ $input }}">
    </div>
    <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors">Tambah</button>
</form>

{{-- DAFTAR --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
            <tr>
                <th class="px-4 py-3 text-left font-semibold">Nama Bidang</th>
                <th class="px-4 py-3 text-right font-semibold">Reviewer</th>
                <th class="px-4 py-3 text-right font-semibold">Naskah</th>
                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($fields as $field)
                <tr x-data="{ editing: false }">
                    <td class="px-4 py-3 text-gray-800">
                        <span x-show="!editing">{{ $field->name }}</span>
                        <form x-show="editing" x-cloak id="edit-{{ $field->id }}" method="POST" action="{{ route('admin.fields.update', $field) }}">
                            @csrf @method('PUT')
                            <input name="name" type="text" maxlength="120" required value="{{ $field->name }}" class="{{ $input }}">
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $field->reviewers_count }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ $field->manuscripts_count }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button type="button" x-show="!editing" @click="editing = true" class="text-sm font-semibold text-red-600 hover:text-red-700">Ubah</button>
                        <span x-show="editing" x-cloak>
                            <button type="submit" form="edit-{{ $field->id }}" class="text-sm font-semibold text-red-600 hover:text-red-700">Simpan</button>
                            <button type="button" @click="editing = false" class="ml-3 text-sm text-gray-500 hover:text-gray-700">Batal</button>
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">Belum ada bidang keahlian.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
