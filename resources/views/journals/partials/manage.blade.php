@php
    $input = 'text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white';
    $prefix = auth()->user()->isAdmin() ? 'admin' : 'editor';
    $roleLabel = ['Editor' => 'Editor', 'Reviewer' => 'Reviewer', 'Author' => 'Penulis'];
@endphp

{{-- Template --}}
<section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-base font-semibold text-gray-800">Template Naskah</h2>
    </div>
    <div class="p-6 space-y-4">
        <p class="text-sm text-gray-600">
            @if ($journal->hasTemplate())
                Saat ini: <strong>{{ $journal->template_original_name ?: 'Template bawaan' }}</strong>
                @if ($journal->template_version > 0) (versi {{ $journal->template_version }})@endif.
                <a href="{{ route('journals.template', $journal) }}" class="font-semibold text-red-600 hover:underline">Unduh</a>
            @else
                Belum ada template. Unggah agar anggota jurnal dapat mengunduhnya.
            @endif
        </p>
        <form method="POST" action="{{ route('journals.template.upload', $journal) }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="template-{{ $journal->id }}" class="block text-xs font-semibold text-gray-600 mb-1.5">Berkas baru (DOCX atau PDF, maks. 10 MB)</label>
                <input id="template-{{ $journal->id }}" name="template" type="file" accept=".docx,.pdf" required class="{{ $input }}">
            </div>
            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Unggah Template</button>
        </form>
        @error('template')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</section>

{{-- Anggota --}}
@foreach ($roles as $role)
    <section class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">{{ $roleLabel[$role] }} Jurnal</h2>
            <span class="text-xs text-gray-400">{{ ($members[$role] ?? collect())->count() }} anggota</span>
        </div>

        <form method="POST" action="{{ route($prefix . '.journals.members.add', $journal) }}" class="px-6 py-4 flex flex-wrap items-end gap-3 border-b border-gray-100">
            @csrf
            <input type="hidden" name="role" value="{{ $role }}">
            <div class="min-w-[16rem]">
                <label for="add-{{ $role }}-{{ $journal->id }}" class="block text-xs font-semibold text-gray-600 mb-1.5">Tambah {{ strtolower($roleLabel[$role]) }}</label>
                <select id="add-{{ $role }}-{{ $journal->id }}" name="user_id" required class="{{ $input }} w-full">
                    <option value="">— Pilih akun —</option>
                    @foreach ($candidates[$role] as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tambahkan</button>
        </form>

        <ul class="divide-y divide-gray-100">
            @forelse (($members[$role] ?? collect()) as $m)
                <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                    <span><span class="font-medium text-gray-800">{{ $m->name }}</span> <span class="text-gray-400">{{ $m->email }}</span></span>
                    <form method="POST" action="{{ route($prefix . '.journals.members.remove', [$journal, $m, $role]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-red-600">Keluarkan</button>
                    </form>
                </li>
            @empty
                <li class="px-6 py-6 text-sm text-gray-400">Belum ada {{ strtolower($roleLabel[$role]) }}.</li>
            @endforelse
        </ul>
    </section>
@endforeach
