@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50">

    {{-- HEADER --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('reviewer.manuscripts.index') }}"
                class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                ‹
            </a>

            <h1 class="text-2xl font-bold text-gray-900">
                Hasil Review Naskah
            </h1>
        </div>
    </div>

    @forelse ($reviews as $review)

        {{-- INFORMASI NASKAH --}}
        <div class="mb-8 rounded-lg bg-white p-6 shadow-sm">

            <div class="mb-1 text-base text-gray-900">
                <strong>Judul:</strong>
                {{ $review->manuscript->title ?? 'Naskah tidak ditemukan' }}
            </div>

            <div class="mb-1 text-base text-gray-900">
                <strong>Bidang:</strong>
                {{ $review->manuscript->researchField->name ?? '-' }}
            </div>

            <div class="mb-1 text-base text-gray-900">
                <strong>Tanggal Penugasan:</strong>
                {{ $review->created_at ? $review->created_at->format('d F Y') : '-' }}
            </div>

            <div class="text-base text-gray-900">
                <strong>Batas Review:</strong>
                {{ $review->due_at ? $review->due_at->format('d F Y') : '-' }}
            </div>

        </div>

        {{-- HASIL PENILAIAN --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">

            <h2 class="mb-5 text-xl font-bold text-gray-900">
                Hasil Penilaian
            </h2>

            <div class="mb-3 text-base text-gray-900">
                <strong>Relevansi Topik:</strong>
                {{ $review->topic_relevance ?? '-' }}
            </div>

            <div class="mb-3 text-base text-gray-900">
                <strong>Metodologi:</strong>
                {{ $review->methodology ?? '-' }}
            </div>

            <div class="mb-5 text-base text-gray-900">
                <strong>Rekomendasi:</strong>
                {{ $review->recommendation ?? '-' }}
            </div>

            <div class="mb-2 text-base text-gray-900">
                Komentar
            </div>

            <div class="min-h-36 rounded-lg border border-gray-400 bg-gray-200 p-4 text-base text-gray-900">
                {{ $review->comments ?? '-' }}
            </div>

        </div>

        {{-- TOMBOL MINTA PERUBAHAN --}}
        <div class="mt-4 mb-8 flex justify-center">
            <button
                type="button"
                class="rounded-lg border border-gray-200 bg-white px-8 py-4 text-base font-medium text-gray-900 hover:bg-gray-100">
                Minta Perubahan Review
            </button>
        </div>

    @empty

        <div class="rounded-lg bg-white p-8 text-center text-gray-500 shadow-sm">
            Belum ada naskah yang selesai direview.
        </div>

    @endforelse

</div>

@endsection