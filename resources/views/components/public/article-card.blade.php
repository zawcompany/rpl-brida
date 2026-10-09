{{-- Kartu artikel terbit. Prop: article (Manuscript dengan author, researchField, issue ter-load). --}}
@props(['article', 'showIssue' => true])

<article class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
    <div class="mb-2 flex flex-wrap items-center gap-2">
        @if ($article->researchField)
            <x-editor.badge color="bg-red-50 text-red-700">{{ $article->researchField->name }}</x-editor.badge>
        @endif
        @if ($showIssue && $article->issue)
            <x-editor.badge color="bg-gray-100 text-gray-600">{{ $article->issue->label }}</x-editor.badge>
        @endif
    </div>

    <h3 class="text-lg font-semibold leading-snug text-gray-900">
        <a href="{{ route('reader.article', $article->id) }}" class="hover:text-red-700">{{ $article->title }}</a>
    </h3>

    <p class="mt-1 text-sm text-gray-500">
        {{ $article->authors_label }}
        <span class="mx-1">·</span>
        {{ ($article->published_at ?? $article->issue?->published_at)?->format('d M Y') }}
    </p>

    <p class="mt-3 flex-1 text-sm leading-relaxed text-gray-700">{{ \Illuminate\Support\Str::limit((string) $article->abstract, 220) }}</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('reader.article', $article->id) }}"
           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Baca Selengkapnya</a>
        <x-public.download-button :article="$article" />
    </div>
</article>
