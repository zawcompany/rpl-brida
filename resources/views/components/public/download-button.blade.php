{{-- Tombol Unduh PDF artikel terbit (menambah penghitung unduhan). Prop: article (Manuscript), class tambahan via attributes. --}}
@props(['article'])

@if ($article->final_file_path)
    <a href="{{ route('articles.download', $article->id) }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700']) }}>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Unduh PDF
    </a>
@endif
