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

    <div class="grid grid-cols-2 gap-4 pb-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
        @forelse ($journals as $journal)
            <x-public.journal-card :journal="$journal" :joined="$journal->joined" />
        @empty
            <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">Belum ada jurnal aktif.</div>
        @endforelse
    </div>
</div>
@endsection
