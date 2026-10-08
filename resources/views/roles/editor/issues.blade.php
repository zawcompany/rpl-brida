@extends('layouts.app')

@section('content')
@php
    $input  = 'w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white';
    $label  = 'block text-xs font-semibold text-gray-600 mb-1.5';
    $th     = 'px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider';
    $draft  = $activeIssue?->isDraft();
    $ready  = $draft && $issueManuscripts->isNotEmpty() && $issueManuscripts->every(fn ($m) => $m->final_file_path);
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Edisi & Publikasi</h1>
    <p class="text-sm text-gray-500 mt-1">Kelola edisi jurnal, tetapkan naskah yang disetujui, unggah layout final, lalu terbitkan.</p>
</div>

@if (session('success'))
    <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- ============================================================ --}}
{{-- BAGIAN 1: KELOLA EDISI --}}
{{-- ============================================================ --}}
<section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800">1. Kelola Edisi</h2>
    </div>

    <form method="POST"
          action="{{ $editingIssue ? route('editor.issues.update', $editingIssue) : route('editor.issues.store') }}"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 px-6 py-5 border-b border-gray-100">
        @csrf
        @if ($editingIssue) @method('PUT') @endif

        <div>
            <label class="{{ $label }}" for="i-volume">Volume</label>
            <input id="i-volume" type="number" min="1" name="volume" required value="{{ old('volume', $editingIssue?->volume) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}" for="i-number">Nomor</label>
            <input id="i-number" type="number" min="1" name="number" required value="{{ old('number', $editingIssue?->number) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $label }}" for="i-year">Tahun</label>
            <input id="i-year" type="number" min="2000" max="2100" name="year" required value="{{ old('year', $editingIssue?->year ?? now()->year) }}" class="{{ $input }}">
        </div>
        <div class="sm:col-span-2 lg:col-span-2">
            <label class="{{ $label }}" for="i-title">Judul Edisi <span class="font-normal text-gray-400">(opsional)</span></label>
            <input id="i-title" type="text" name="title" maxlength="255" value="{{ old('title', $editingIssue?->title) }}" class="{{ $input }}">
        </div>
        <div class="sm:col-span-2 lg:col-span-5 flex items-center gap-3">
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                {{ $editingIssue ? 'Simpan Perubahan' : 'Tambah Edisi' }}
            </button>
            @if ($editingIssue)
                <a href="{{ route('editor.issues.index', ['issue' => $editingIssue->id]) }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="{{ $th }}">Volume</th>
                    <th class="{{ $th }}">Nomor</th>
                    <th class="{{ $th }}">Tahun</th>
                    <th class="{{ $th }}">Judul Edisi</th>
                    <th class="{{ $th }}">Naskah</th>
                    <th class="{{ $th }}">Status</th>
                    <th class="{{ $th }}">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($issues as $issue)
                <tr class="{{ $activeIssue?->id === $issue->id ? 'bg-red-50/40' : 'hover:bg-gray-50/60' }}">
                    <td class="px-6 py-3 text-sm text-gray-800">{{ $issue->volume }}</td>
                    <td class="px-6 py-3 text-sm text-gray-800">{{ $issue->number }}</td>
                    <td class="px-6 py-3 text-sm text-gray-800">{{ $issue->year }}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ $issue->title ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ $issue->manuscripts_count }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $issue->isPublished() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $issue->isPublished() ? 'Published' : 'Draft' }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <a href="{{ route('editor.issues.index', ['issue' => $issue->id]) }}" class="text-red-600 hover:text-red-700">Kelola Naskah</a>
                            @if ($issue->isDraft())
                                <a href="{{ route('editor.issues.index', ['issue' => $activeIssue?->id, 'edit' => $issue->id]) }}" class="text-gray-600 hover:text-gray-800">Ubah</a>
                                @if ($issue->manuscripts_count === 0)
                                <form method="POST" action="{{ route('editor.issues.destroy', $issue) }}" onsubmit="return confirm('Hapus edisi ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600">Hapus</button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                    @include('roles.editor.partials.empty-row', ['colspan' => 7, 'message' => 'Belum ada edisi. Tambahkan edisi pertama di atas.'])
                @endforelse
            </tbody>
        </table>
    </div>
</section>

{{-- ============================================================ --}}
{{-- BAGIAN 2: PENETAPAN NASKAH & UPLOAD LAYOUT FINAL --}}
{{-- ============================================================ --}}
<section class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-gray-100">
        <div>
            <h2 class="text-base font-semibold text-gray-800">2. Penetapan Naskah & Layout Final</h2>
            @if ($activeIssue)
                <p class="text-xs text-gray-400 mt-0.5">
                    Edisi aktif: <span class="font-medium text-gray-600">{{ $activeIssue->label }}</span>
                    {{ $activeIssue->title ? '— ' . $activeIssue->title : '' }}
                </p>
            @endif
        </div>

        @if ($activeIssue && $draft)
            <form method="POST" action="{{ route('editor.issues.publish', $activeIssue) }}"
                  onsubmit="return confirm('Terbitkan edisi ini? Semua naskahnya akan berstatus Diterbitkan dan tidak dapat diubah lagi.')">
                @csrf
                <button type="submit" @disabled(! $ready)
                        title="{{ $ready ? '' : 'Tambahkan naskah dan pastikan semuanya memiliki PDF final.' }}"
                        class="px-5 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Publikasikan Edisi
                </button>
            </form>
        @endif
    </div>

    @if (! $activeIssue)
        <p class="px-6 py-12 text-center text-sm text-gray-400">Buat edisi terlebih dahulu untuk menetapkan naskah.</p>
    @else
        @if ($draft)
        <form method="POST" action="{{ route('editor.issues.manuscripts.attach', $activeIssue) }}" enctype="multipart/form-data"
              class="grid grid-cols-1 lg:grid-cols-3 gap-4 px-6 py-5 border-b border-gray-100">
            @csrf
            <div>
                <label class="{{ $label }}" for="a-manuscript">Naskah Disetujui</label>
                <select id="a-manuscript" name="manuscript_id" required class="{{ $input }}">
                    <option value="">— Pilih Naskah —</option>
                    @foreach ($eligibleManuscripts as $m)
                        <option value="{{ $m->id }}" @selected(old('manuscript_id') == $m->id)>{{ $m->title }} — {{ $m->author?->name }}</option>
                    @endforeach
                </select>
                @if ($eligibleManuscripts->isEmpty())
                    <p class="text-xs text-gray-400 mt-1">Tidak ada naskah berstatus Disetujui yang tersedia.</p>
                @endif
            </div>
            <div>
                <label class="{{ $label }}" for="a-file">PDF Final / Camera-Ready</label>
                <input id="a-file" type="file" name="final_file" accept="application/pdf" required
                       class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="text-xs text-gray-400 mt-1">PDF, maksimal 20 MB.</p>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    Tambahkan ke Edisi
                </button>
            </div>
        </form>
        @endif

        @if ($draft && $eligibleManuscripts->isNotEmpty())
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Antrean Naskah Disetujui — unduh berkas untuk layouting</p>
            <ul class="divide-y divide-gray-100 text-sm">
                @foreach ($eligibleManuscripts as $m)
                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                    <span class="text-gray-700">{{ $m->title }} <span class="text-gray-400">— {{ $m->author?->name }}</span></span>
                    @if ($m->source_file_url)
                        <a href="{{ $m->source_file_url }}" target="_blank" rel="noopener" download class="text-xs font-medium text-red-600 hover:text-red-700">
                            Unduh {{ $m->isRevision() ? 'Berkas Revisi' : 'Berkas Asli' }}
                        </a>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="{{ $th }}">Naskah</th>
                        <th class="{{ $th }}">Penulis</th>
                        <th class="{{ $th }}">Status</th>
                        <th class="{{ $th }}">Berkas Author</th>
                        <th class="{{ $th }}">PDF Final</th>
                        @if ($draft)<th class="{{ $th }}">Aksi</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($issueManuscripts as $m)
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-6 py-3 text-sm font-medium text-gray-800 max-w-[280px] truncate" title="{{ $m->title }}">{{ $m->title }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600">{{ $m->author?->name ?? '—' }}</td>
                        <td class="px-6 py-3">@include('roles.editor.partials.status-badge', ['status' => $m->status, 'label' => $m->status_label])</td>
                        <td class="px-6 py-3 text-sm">
                            @if ($m->source_file_url)
                                <a href="{{ $m->source_file_url }}" target="_blank" rel="noopener" download class="text-gray-600 hover:text-red-600">
                                    {{ $m->isRevision() ? 'Revisi' : 'Asli' }}
                                </a>
                            @else — @endif
                        </td>
                        <td class="px-6 py-3 text-sm">
                            @if ($m->final_file_url)
                                <a href="{{ $m->final_file_url }}" target="_blank" rel="noopener" class="text-red-600 hover:text-red-700 font-medium">
                                    {{ $m->final_original_name ?? 'Lihat PDF' }}
                                </a>
                            @else
                                <span class="text-xs text-orange-600">Belum diunggah</span>
                            @endif
                        </td>
                        @if ($draft)
                        <td class="px-6 py-3">
                            <form method="POST" action="{{ route('editor.issues.manuscripts.detach', [$activeIssue, $m]) }}" onsubmit="return confirm('Keluarkan naskah dari edisi?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-gray-500 hover:text-red-600">Keluarkan</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                        @include('roles.editor.partials.empty-row', ['colspan' => $draft ? 6 : 5, 'message' => 'Belum ada naskah pada edisi ini.'])
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
