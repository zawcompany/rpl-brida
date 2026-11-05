@extends('layouts.guest', ['title' => $issue->label . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
    <a href="{{ route('archives.index', \App\Support\Nav::keep()) }}" class="text-sm text-gray-500 hover:text-red-600">← Arsip terbitan</a>

    <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">

        {{-- Sampul besar (kiri) --}}
        <aside>
            <div class="mx-auto w-full max-w-[14rem] lg:mx-0 lg:max-w-none">
                <img src="{{ $issue->cover_url }}" alt="Sampul {{ $issue->label }}"
                     class="aspect-[3/4] w-full rounded-xl border border-gray-200 bg-gray-100 object-cover shadow-md">
            </div>
        </aside>

        {{-- Keterangan edisi (kanan) --}}
        <div class="min-w-0 self-center">
            @if ($issue->journal)
                <a href="{{ route('journals.show', ['journal' => $issue->journal] + \App\Support\Nav::keep()) }}"
                   class="text-xs font-semibold uppercase tracking-widest text-red-600 hover:text-red-700">{{ $issue->journal->name }}</a>
            @endif
            <h2 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $issue->label }}</h2>
            @if ($issue->title)<p class="mt-1 text-lg text-gray-600">{{ $issue->title }}</p>@endif
            <dl class="mt-5 flex flex-wrap gap-x-10 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-400">Artikel</dt>
                    <dd class="font-semibold text-gray-800">{{ $issue->manuscripts->count() }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400">Terbit</dt>
                    <dd class="font-semibold text-gray-800">{{ $issue->published_at?->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-10">
        @if ($issue->manuscripts->isNotEmpty())
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($issue->manuscripts as $article)
                    <x-public.article-card :article="$article->setRelation('issue', $issue)" :show-issue="false" />
                @endforeach
            </div>
        @else
            <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">Edisi ini belum memiliki artikel.</div>
        @endif
    </div>
</div>
@endsection
