@extends('layouts.app', ['title' => 'Kelola Jurnal — SIMPIL'])

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Kelola Jurnal</h1>
    <p class="text-sm text-gray-500 mt-1">Buat jurnal, tunjuk editor, dan atur template serta anggotanya.</p>
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

<section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-800">Jurnal Baru</h2></div>
    <form method="POST" action="{{ route('admin.journals.store') }}" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div class="md:col-span-2">
            <label for="name" class="block text-xs font-semibold text-gray-600 mb-1.5">Nama jurnal <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name') }}" class="{{ $input }}">
        </div>
        <div>
            <label for="abbreviation" class="block text-xs font-semibold text-gray-600 mb-1.5">Singkatan</label>
            <input id="abbreviation" name="abbreviation" type="text" maxlength="30" value="{{ old('abbreviation') }}" class="{{ $input }}">
        </div>
        <div class="md:col-span-2">
            <label for="field" class="block text-xs font-semibold text-gray-600 mb-1.5">Bidang</label>
            <input id="field" name="field" type="text" maxlength="120" value="{{ old('field') }}" placeholder="mis. Kesehatan Masyarakat" class="{{ $input }}">
        </div>
        <div>
            <label for="cover" class="block text-xs font-semibold text-gray-600 mb-1.5">Cover jurnal <span class="font-normal text-gray-400">(opsional)</span></label>
            <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <p class="text-xs text-gray-400 mt-1">JPG, PNG, atau WebP, maksimal 2 MB. Rasio 3:4 paling pas.</p>
        </div>
        <div class="md:col-span-3">
            <label for="description" class="block text-xs font-semibold text-gray-600 mb-1.5">Deskripsi</label>
            <textarea id="description" name="description" rows="2" maxlength="2000" class="{{ $input }}">{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-3"><button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Buat Jurnal</button></div>
    </form>
</section>

<section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                @foreach (['Jurnal', 'Bidang', 'Editor', 'Reviewer', 'Penulis', 'Naskah', 'Template', 'Status', ''] as $h)
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($journals as $j)
                <tr>
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $j->name }}@if ($j->abbreviation) <span class="text-gray-400">· {{ $j->abbreviation }}</span>@endif</td>
                    <td class="px-6 py-3 text-gray-600">{{ $j->field ?: '—' }}</td>
                    <td class="px-6 py-3">{{ $j->editors_count }}</td>
                    <td class="px-6 py-3">{{ $j->reviewers_count }}</td>
                    <td class="px-6 py-3">{{ $j->authors_count }}</td>
                    <td class="px-6 py-3">{{ $j->manuscripts_count }}</td>
                    <td class="px-6 py-3">{{ $j->hasTemplate() ? 'v' . max($j->template_version, 1) : '—' }}</td>
                    <td class="px-6 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $j->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $j->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="px-6 py-3 text-right"><a href="{{ route('admin.journals.show', $j) }}" class="font-semibold text-red-600 hover:underline">Kelola</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
