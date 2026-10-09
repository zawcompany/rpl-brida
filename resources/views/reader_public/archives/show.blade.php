@extends('layouts.guest', ['title' => $issue->label . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
    <div class="mb-8 flex items-start gap-3">
        <a href="{{ route('archives.index') }}" class="mt-1 text-2xl leading-none text-gray-900 hover:text-gray-500" aria-label="Kembali ke arsip">←</a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $issue->label }}</h2>
            @if ($issue->title)<p class="mt-1 text-gray-500">{{ $issue->title }}</p>@endif
            <p class="mt-1 text-sm text-gray-400">{{ $issue->manuscripts->count() }} artikel · terbit {{ $issue->published_at?->format('d M Y') }}</p>
        </div>
    </div>

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
@endsection
