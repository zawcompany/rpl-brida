@extends('layouts.app', ['title' => $complaint->ticket . ' — Pengaduan'])

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <a href="{{ route('admin.complaints.index') }}" class="text-sm text-gray-500 hover:text-red-600">← Semua pengaduan</a>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ $complaint->subject }}</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $complaint->ticket }} · {{ $complaint->category_label }} · dikirim {{ $complaint->created_at->format('d M Y H:i') }}</p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6 space-y-5">
    <dl class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm">
        <div><dt class="text-gray-500">Pengadu</dt><dd class="font-semibold text-gray-900">{{ $complaint->user?->name ?? '—' }}</dd><dd class="text-xs text-gray-500">{{ $complaint->user?->email }}</dd></div>
        <div><dt class="text-gray-500">Status</dt><dd class="font-semibold text-gray-900">{{ $complaint->status_label }}</dd></div>
        <div><dt class="text-gray-500">Batas tindak lanjut</dt><dd class="font-semibold {{ $complaint->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">{{ $complaint->due_at->format('d M Y') }}</dd></div>
        <div><dt class="text-gray-500">Naskah terkait</dt><dd class="font-semibold text-gray-900">{{ $complaint->manuscript?->title ?? '—' }}</dd></div>
    </dl>
    <div>
        <h2 class="text-sm font-semibold text-gray-700">Uraian pengadu</h2>
        <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $complaint->description }}</p>
    </div>
    @if ($complaint->handler)
        <p class="text-xs text-gray-400">Terakhir ditangani {{ $complaint->handler->name }}, {{ $complaint->responded_at?->format('d M Y H:i') }}</p>
    @endif
</section>

<section class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-800">Tanggapan</h2></div>
    <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="p-6 space-y-4">
        @csrf @method('PUT')
        <div class="max-w-xs">
            <label for="status" class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
            <select id="status" name="status" class="{{ $input }}">
                @foreach (\App\Models\Complaint::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $complaint->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="response" class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggapan untuk pengadu</label>
            <textarea id="response" name="response" rows="5" maxlength="5000" class="{{ $input }}">{{ old('response', $complaint->response) }}</textarea>
        </div>
        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Simpan &amp; Beri Tahu Pengadu</button>
    </form>
</section>
@endsection
