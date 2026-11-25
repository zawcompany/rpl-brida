@extends('layouts.app', ['title' => $complaint->ticket . ' — Pengaduan'])

@section('content')
<div class="mb-6">
    <a href="{{ route('complaints.index') }}" class="text-sm text-gray-500 hover:text-red-600">← Pengaduan saya</a>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $complaint->subject }}</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $complaint->ticket }} · {{ $complaint->category_label }} · dikirim {{ $complaint->created_at->format('d M Y H:i') }}</p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif

<section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div><dt class="text-gray-500">Status</dt><dd class="font-semibold text-gray-900">{{ $complaint->status_label }}</dd></div>
        <div><dt class="text-gray-500">Batas tindak lanjut</dt><dd class="font-semibold text-gray-900">{{ $complaint->due_at->format('d M Y') }}</dd></div>
        <div><dt class="text-gray-500">Naskah terkait</dt><dd class="font-semibold text-gray-900">{{ $complaint->manuscript?->title ?? '—' }}</dd></div>
    </dl>

    <div>
        <h2 class="text-sm font-semibold text-gray-700">Uraian Anda</h2>
        <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $complaint->description }}</p>
    </div>

    <div class="border-t border-gray-100 pt-5">
        <h2 class="text-sm font-semibold text-gray-700">Tanggapan petugas</h2>
        @if ($complaint->response)
            <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $complaint->response }}</p>
            <p class="mt-2 text-xs text-gray-400">Diperbarui {{ $complaint->responded_at?->format('d M Y H:i') }}</p>
        @else
            <p class="mt-1 text-sm text-gray-500">Belum ada tanggapan. Anda akan menerima notifikasi saat pengaduan ditanggapi.</p>
        @endif
    </div>
</section>
@endsection
