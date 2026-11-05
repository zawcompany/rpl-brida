{{-- Ubin edisi terbit: cover dan judul. Prop: issue (dengan relasi journal). --}}
@props(['issue'])

<a href="{{ route('archives.show', ['issue' => $issue] + \App\Support\Nav::keep()) }}" class="group block">
    <img src="{{ $issue->cover_url }}" alt="Sampul {{ $issue->label }}" loading="lazy"
         class="aspect-[3/4] w-full rounded-lg border border-gray-200 bg-gray-100 object-cover shadow-sm transition group-hover:shadow-md">
    <p class="mt-3 font-semibold leading-snug text-gray-900 group-hover:text-red-700">{{ $issue->title ?: $issue->label }}</p>
    <p class="mt-0.5 text-xs text-gray-500">{{ $issue->journal?->short_name }}@if ($issue->title) · {{ $issue->label }}@endif</p>
</a>
