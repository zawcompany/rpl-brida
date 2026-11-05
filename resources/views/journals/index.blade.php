@extends('layouts.guest', ['title' => 'Jurnal — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
    <div class="mb-8 max-w-3xl">
        <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">Jurnal</h2>
        <p class="mt-1 text-gray-500">
            Setiap jurnal punya kelompok penulis dan template naskahnya sendiri. Bergabung ke jurnal yang Anda tuju
            untuk mengunduh templatenya dan mengirim naskah.
        </p>
    </div>

    @if (session('success'))
        <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 pb-4">
        @forelse ($journals as $journal)
            <a href="{{ route('journals.show', $journal) }}"
               class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="text-lg font-bold text-gray-900 leading-snug">{{ $journal->name }}</h3>
                    @if ($journal->joined)
                        <span class="flex-shrink-0 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Anggota</span>
                    @endif
                </div>
                @if ($journal->abbreviation)
                    <p class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-red-600">{{ $journal->abbreviation }}</p>
                @endif
                <p class="mt-3 flex-1 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($journal->description ?: 'Belum ada deskripsi.', 140) }}</p>
                <dl class="mt-4 flex gap-6 border-t border-gray-100 pt-4 text-sm">
                    <div><dt class="text-xs text-gray-400">Penulis</dt><dd class="font-semibold text-gray-800">{{ $journal->authors_count }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Artikel terbit</dt><dd class="font-semibold text-gray-800">{{ $journal->articles_count }}</dd></div>
                    <div><dt class="text-xs text-gray-400">Template</dt><dd class="font-semibold text-gray-800">{{ $journal->hasTemplate() ? 'Tersedia' : 'Belum ada' }}</dd></div>
                </dl>
            </a>
        @empty
            <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">Belum ada jurnal aktif.</div>
        @endforelse
    </div>
</div>
@endsection
