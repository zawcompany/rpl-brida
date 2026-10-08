@extends('layouts.app')

@section('content')
@php
    $input = 'w-full text-sm border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white';
    $oldCoAuthors = collect(old('co_authors', []))->map(fn ($c) => ['name' => $c['name'] ?? '', 'email' => $c['email'] ?? ''])->values();
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Naskah Baru</h1>
    <p class="text-sm text-gray-500 mt-1">Lengkapi data berikut untuk mengajukan naskah ke tim editor.</p>
</div>

@if ($errors->any())
    <div role="alert" class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
        Pengajuan belum berhasil. Periksa kembali isian yang ditandai di bawah.
    </div>
@endif

<form method="POST" action="{{ route('author.manuscripts.store') }}" enctype="multipart/form-data"
      x-data="{
          coAuthors: {{ Js::from($oldCoAuthors) }},
          fileName: '',
          fileError: '',
          add() { if (this.coAuthors.length < 10) this.coAuthors.push({ name: '', email: '' }); },
          remove(i) { this.coAuthors.splice(i, 1); },
          pick(e) {
              const f = e.target.files[0];
              this.fileError = '';
              if (f && f.size > 10 * 1024 * 1024) { this.fileError = 'Ukuran berkas maksimal 10 MB.'; e.target.value = ''; this.fileName = ''; return; }
              this.fileName = f ? f.name : '';
          },
      }"
      class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6 w-full">
    @csrf

    {{-- Judul --}}
    <div>
        <label for="title" class="block text-sm font-semibold text-gray-700 mb-1.5">Judul Naskah <span class="text-red-500">*</span></label>
        <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="255"
               class="{{ $input }} {{ $errors->has('title') ? 'border-red-400' : 'border-gray-300' }}">
        @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Bidang keahlian --}}
        <div>
            <label for="research_field_id" class="block text-sm font-semibold text-gray-700 mb-1.5">Bidang Keahlian / Kategori Riset <span class="text-red-500">*</span></label>
            <select id="research_field_id" name="research_field_id" required
                    class="{{ $input }} {{ $errors->has('research_field_id') ? 'border-red-400' : 'border-gray-300' }}">
                <option value="">— Pilih Bidang —</option>
                @foreach ($fields as $field)
                    <option value="{{ $field->id }}" @selected(old('research_field_id') == $field->id)>{{ $field->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Dipakai editor untuk memilih reviewer yang sesuai.</p>
            @error('research_field_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Keywords --}}
        <div>
            <label for="keywords" class="block text-sm font-semibold text-gray-700 mb-1.5">Kata Kunci / Keywords <span class="text-red-500">*</span></label>
            <input id="keywords" name="keywords" type="text" value="{{ old('keywords') }}" required maxlength="255"
                   placeholder="mis. kebijakan publik, inovasi daerah"
                   class="{{ $input }} {{ $errors->has('keywords') ? 'border-red-400' : 'border-gray-300' }}">
            <p class="text-xs text-gray-400 mt-1">Pisahkan dengan koma.</p>
            @error('keywords') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Abstrak --}}
    <div>
        <label for="abstract" class="block text-sm font-semibold text-gray-700 mb-1.5">Abstrak <span class="text-red-500">*</span></label>
        <textarea id="abstract" name="abstract" rows="7" required maxlength="5000"
                  class="{{ $input }} resize-y {{ $errors->has('abstract') ? 'border-red-400' : 'border-gray-300' }}">{{ old('abstract') }}</textarea>
        @error('abstract') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Penulis pendamping --}}
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-semibold text-gray-700">Penulis Pendamping <span class="font-normal text-gray-400">(opsional)</span></label>
            <button type="button" @click="add()" x-show="coAuthors.length < 10"
                    class="text-xs font-medium text-red-600 hover:text-red-700">+ Tambah Penulis</button>
        </div>
        <div class="space-y-2">
            <template x-for="(c, i) in coAuthors" :key="i">
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" :name="`co_authors[${i}][name]`" x-model="c.name" placeholder="Nama penulis" maxlength="255"
                           class="{{ $input }} border-gray-300">
                    <input type="email" :name="`co_authors[${i}][email]`" x-model="c.email" placeholder="Email (opsional)" maxlength="255"
                           class="{{ $input }} border-gray-300">
                    <button type="button" @click="remove(i)" aria-label="Hapus penulis"
                            class="px-3 py-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors self-start sm:self-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <p x-show="!coAuthors.length" class="text-xs text-gray-400">Belum ada penulis pendamping.</p>
        </div>
        @foreach ($errors->get('co_authors.*') as $messages)
            <p class="text-xs text-red-600 mt-1">{{ $messages[0] }}</p>
        @endforeach
        @error('co_authors') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Berkas --}}
    <div>
        <label for="file" class="block text-sm font-semibold text-gray-700 mb-1.5">
            Unggah Berkas Naskah Utama <span class="text-red-500">*</span>
            <span class="font-normal text-gray-400">(PDF/DOCX, maks. 10 MB)</span>
        </label>
        <input id="file" name="file" type="file" accept=".pdf,.docx" required @change="pick($event)"
               class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-white file:mr-3 file:py-2.5 file:px-4 file:border-0 file:bg-red-50 file:text-red-700 file:font-medium hover:file:bg-red-100">
        <p x-show="fileError" x-text="fileError" class="text-xs text-red-600 mt-1"></p>
        @error('file') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
        <a href="{{ route('author.manuscripts.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</a>
        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors">
            Ajukan Naskah
        </button>
    </div>
</form>
@endsection
