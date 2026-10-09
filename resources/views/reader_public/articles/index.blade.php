@extends('layouts.guest')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">Telusuri Artikel</h2>
        <p class="mt-1 text-gray-500">Temukan berbagai hasil riset dan publikasi ilmiah.</p>
    </div>

    {{-- Pencarian --}}
    <form method="GET" action="{{ route('reader.index') }}">
        @if (! empty($filters['field'])) <input type="hidden" name="field" value="{{ $filters['field'] }}"> @endif

        <div class="flex items-center rounded-full border border-gray-300 bg-white px-5 py-3 shadow-sm focus-within:border-red-500 focus-within:ring-1 focus-within:ring-red-500">
            <svg class="mr-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.04 6.04a7.5 7.5 0 0 0 10.61 10.61Z"/></svg>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                   placeholder="Cari judul, abstrak, atau kata kunci artikel"
                   class="w-full bg-transparent text-gray-700 outline-none focus:outline-none focus:ring-0 border-0">
            <button type="submit" class="ml-3 rounded-full bg-red-600 px-5 py-1.5 text-sm font-semibold text-white hover:bg-red-700">Cari</button>
        </div>
        @error('q') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </form>

    {{-- Filter bidang --}}
    @if ($fields->isNotEmpty())
    <div class="mt-4 flex flex-wrap gap-2">
        @php $chip = 'rounded-full border px-4 py-1.5 text-sm transition'; @endphp
        <a href="{{ route('reader.index', array_filter(['q' => $filters['q'] ?? null])) }}"
           class="{{ $chip }} {{ empty($filters['field']) ? 'border-red-600 bg-red-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">Semua</a>
        @foreach ($fields as $field)
            <a href="{{ route('reader.index', array_filter(['q' => $filters['q'] ?? null, 'field' => $field->id])) }}"
               class="{{ $chip }} {{ (int) ($filters['field'] ?? 0) === $field->id ? 'border-red-600 bg-red-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-100' }}">{{ $field->name }}</a>
        @endforeach
    </div>
    @endif

    <p class="mt-6 mb-3 text-sm text-gray-500">{{ $articles->total() }} artikel ditemukan</p>

    {{-- Daftar artikel --}}
    @if ($articles->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($articles as $article)
                <x-public.article-card :article="$article" />
            @endforeach
        </div>
        @if ($articles->hasPages())
            <div class="mt-8 text-sm">{{ $articles->links() }}</div>
        @endif
    @else
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">
            Tidak ada artikel yang sesuai. Coba kata kunci atau bidang lain.
        </div>
    @endif
</div>
@endsection
