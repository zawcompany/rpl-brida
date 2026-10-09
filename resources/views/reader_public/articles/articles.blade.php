@extends('layouts.guest', ['title' => $article->title . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">

    {{-- Judul & metadata --}}
    <div class="flex items-start gap-3">
        <a href="{{ route('reader.index') }}" class="mt-1 text-2xl leading-none text-gray-900 hover:text-gray-500" aria-label="Kembali ke daftar artikel">←</a>
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                @if ($article->researchField)
                    <x-editor.badge color="bg-red-50 text-red-700">{{ $article->researchField->name }}</x-editor.badge>
                @endif
                @if ($article->issue)
                    <a href="{{ route('archives.show', $article->issue) }}"><x-editor.badge color="bg-gray-100 text-gray-600">{{ $article->issue->label }}</x-editor.badge></a>
                @endif
            </div>
            <h1 class="text-2xl font-bold leading-tight text-gray-900 sm:text-3xl">{{ $article->title }}</h1>
        </div>
    </div>

    <dl class="mt-6 grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Penulis</dt>
            <dd class="mt-0.5 text-gray-800">
                {{ $article->author?->name }}
                @if ($article->author?->institution)<span class="text-gray-500">({{ $article->author->institution }})</span>@endif
                @foreach ($article->co_authors ?? [] as $co)
                    <span class="text-gray-400">,</span> {{ $co['name'] ?? '' }}
                @endforeach
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal Terbit</dt>
            <dd class="mt-0.5 text-gray-800">{{ ($article->published_at ?? $article->issue?->published_at)?->format('d M Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Edisi</dt>
            <dd class="mt-0.5 text-gray-800">{{ $article->issue?->label ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">DOI</dt>
            <dd class="mt-0.5 text-gray-800">
                @if ($article->doi)
                    <a href="https://doi.org/{{ $article->doi }}" target="_blank" rel="noopener noreferrer" class="text-red-600 hover:underline">{{ $article->doi }}</a>
                @else
                    —
                @endif
            </dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kata Kunci</dt>
            <dd class="mt-0.5 text-gray-800">{{ $article->keywords ?: '—' }}</dd>
        </div>
        <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
            <p class="text-xs text-gray-500">{{ number_format($article->download_count, 0, ',', '.') }} kali diunduh</p>
            <x-public.download-button :article="$article" />
        </div>
    </dl>

    {{-- Abstrak --}}
    <section class="mt-6">
        <h2 class="text-lg font-bold text-gray-900">Abstrak</h2>
        <p class="mt-2 whitespace-pre-line leading-relaxed text-gray-700">{{ $article->abstract }}</p>
    </section>

    {{-- Pratinjau PDF --}}
    <section class="mt-8">
        <h2 class="text-lg font-bold text-gray-900">Baca Artikel</h2>
        @if ($hasPdf)
            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm">
                <iframe src="{{ route('articles.pdf', $article->id) }}" title="PDF {{ $article->title }}" class="h-[75vh] w-full" loading="lazy"></iframe>
            </div>
            <p class="mt-2 text-xs text-gray-500">Jika pratinjau tidak tampil di perangkat Anda, gunakan tombol Unduh PDF.</p>
        @else
            <div class="mt-3 rounded-xl border-2 border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">Berkas PDF artikel ini belum tersedia.</div>
        @endif
    </section>

    {{-- Cara mengutip --}}
    <section class="mt-8" x-data="{ tab: 'apa', copied: false, texts: {{ Js::from($citations) }},
        copy() { navigator.clipboard.writeText(this.texts[this.tab]).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); }); } }">
        <h2 class="text-lg font-bold text-gray-900">Cara Mengutip</h2>
        <div class="mt-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap gap-2" role="tablist">
                @foreach (['apa' => 'APA', 'ieee' => 'IEEE', 'mla' => 'MLA'] as $key => $label)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'; copied = false"
                            :class="tab === '{{ $key }}' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="rounded-lg px-4 py-1.5 text-sm font-medium transition">{{ $label }}</button>
                @endforeach
            </div>
            <p class="mt-4 rounded-lg bg-gray-50 p-4 text-sm leading-relaxed text-gray-800 break-words" x-text="texts[tab]"></p>
            <button type="button" @click="copy()" class="mt-3 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span x-text="copied ? 'Tersalin' : 'Salin Sitasi'"></span>
            </button>
        </div>
    </section>
</div>
@endsection
