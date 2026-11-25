@extends('layouts.app', ['title' => 'Kirim Pemberitahuan — Admin'])

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Kirim Pemberitahuan</h1>
    <p class="text-sm text-gray-500 mt-1">
        Pemberitahuan tampil di menu notifikasi setiap penerima. Gunakan antara lain untuk memberi tahu pengguna bila terjadi
        kegagalan pelindungan data pribadi (paling lambat 3 × 24 jam, UU 27/2022 Pasal 46) atau perubahan kebijakan.
    </p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.announcement.store') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4"
      onsubmit="return confirm('Kirim pemberitahuan ini sekarang?')">
    @csrf
    <div class="max-w-xs">
        <label for="audience" class="block text-xs font-semibold text-gray-600 mb-1.5">Penerima</label>
        <select id="audience" name="audience" class="{{ $input }}">
            @foreach ($audiences as $value => $label)
                <option value="{{ $value }}" @selected(old('audience') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="title" class="block text-xs font-semibold text-gray-600 mb-1.5">Judul</label>
        <input id="title" name="title" type="text" required maxlength="120" value="{{ old('title') }}" class="{{ $input }}">
    </div>
    <div>
        <label for="message" class="block text-xs font-semibold text-gray-600 mb-1.5">Isi pemberitahuan</label>
        <textarea id="message" name="message" rows="5" required maxlength="2000" class="{{ $input }}"
                  placeholder="Untuk kebocoran data, sebutkan: data apa yang terungkap, kapan dan bagaimana, serta upaya penanganan dan pemulihannya.">{{ old('message') }}</textarea>
    </div>
    <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Kirim</button>
</form>
@endsection
