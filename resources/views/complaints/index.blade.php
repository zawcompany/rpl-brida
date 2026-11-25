@extends('layouts.app', ['title' => 'Pengaduan — SIMPIL'])

@section('content')
@php $input = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Pengaduan &amp; Keberatan</h1>
    <p class="text-sm text-gray-500 mt-1">
        Sampaikan keluhan layanan, keberatan atas keputusan editorial, atau permintaan terkait data pribadi Anda.
        Pengaduan dikelola oleh <strong class="text-gray-700">{{ config('simpil.complaints.officer') }}</strong>
        dan ditindaklanjuti paling lambat <strong class="text-gray-700">{{ config('simpil.complaints.response_days') }} hari</strong> sejak diterima.
    </p>
</div>

@if ($errors->any())
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-800">Kirim Pengaduan Baru</h2></div>
    <form method="POST" action="{{ route('complaints.store') }}" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label for="category" class="block text-xs font-semibold text-gray-600 mb-1.5">Jenis pengaduan</label>
            <select id="category" name="category" required class="{{ $input }}">
                @foreach (\App\Models\Complaint::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="manuscript_id" class="block text-xs font-semibold text-gray-600 mb-1.5">Naskah terkait (opsional)</label>
            <select id="manuscript_id" name="manuscript_id" class="{{ $input }}">
                <option value="">Tidak terkait naskah</option>
                @foreach ($manuscripts as $manuscript)
                    <option value="{{ $manuscript->id }}" @selected((int) old('manuscript_id') === $manuscript->id)>{{ \Illuminate\Support\Str::limit($manuscript->title, 90) }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="subject" class="block text-xs font-semibold text-gray-600 mb-1.5">Judul pengaduan</label>
            <input id="subject" name="subject" type="text" required maxlength="150" value="{{ old('subject') }}" class="{{ $input }}">
        </div>
        <div class="md:col-span-2">
            <label for="description" class="block text-xs font-semibold text-gray-600 mb-1.5">Uraian</label>
            <textarea id="description" name="description" rows="5" required minlength="20" maxlength="5000" class="{{ $input }}">{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-2"><button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Kirim Pengaduan</button></div>
    </form>
</section>

<section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100"><h2 class="text-base font-semibold text-gray-800">Pengaduan Saya</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Nomor', 'Judul', 'Jenis', 'Status', 'Dikirim'] as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($complaints as $complaint)
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap"><a href="{{ route('complaints.show', $complaint) }}" class="text-red-600 hover:underline">{{ $complaint->ticket }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-800">{{ $complaint->subject }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $complaint->category_label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $complaint->status_label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $complaint->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Anda belum pernah mengirim pengaduan.'])
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
