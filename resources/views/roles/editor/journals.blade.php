@extends('layouts.app', ['title' => 'Jurnal Saya — SIMPIL'])

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Jurnal Saya</h1>
    <p class="text-sm text-gray-500 mt-1">Kelola template naskah dan reviewer pada jurnal yang Anda pimpin sebagai editor.</p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
@endif

@if ($journals->isEmpty())
    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
        Anda belum ditunjuk sebagai editor di jurnal mana pun. Hubungi admin platform.
    </div>
@else
    @if ($journals->count() > 1)
        <nav class="mb-5 flex flex-wrap gap-2" aria-label="Pilih jurnal">
            @foreach ($journals as $j)
                <a href="{{ route('editor.journals.index', ['jurnal' => $j->id]) }}"
                   class="rounded-full px-4 py-1.5 text-sm font-medium {{ $journal && $j->id === $journal->id ? 'bg-red-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $j->short_name }}</a>
            @endforeach
        </nav>
    @endif

    <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ $journal->name }}</h2>
    @include('journals.partials.manage')
@endif
@endsection
