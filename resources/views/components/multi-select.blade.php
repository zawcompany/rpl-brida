{{--
    Pilihan ganda dengan pencarian: seluruh opsi langsung tampil (daftar gulir), item terpilih tampil sebagai tag.
    Props: name (mis. "research_field_ids"), options (id => label), selected (array id), max (opsional), placeholder.
    Memakai checkbox asli (name="{name}[]"), jadi tetap terkirim dan terpilih walau JavaScript gagal dimuat.
--}}
@props(['name', 'options', 'selected' => [], 'max' => null, 'placeholder' => 'Cari...'])

@php
    $selected = array_map('strval', $selected);
@endphp

@if (collect($options)->isEmpty())
    <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">Daftar pilihan belum tersedia. Hubungi administrator untuk mengisi data bidang.</p>
@else
<div x-data="{
         q: '', max: @js($max),
         picked: @js($selected),
         labels: @js($options),
         get full() { return this.max !== null && this.picked.length >= this.max },
         show(label) { return label.toLowerCase().includes(this.q.toLowerCase()) },
         remove(id) { this.picked = this.picked.filter(p => p !== id) },
     }"
     class="rounded-lg border border-gray-300 bg-white focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-500">

    {{-- Tag terpilih --}}
    <div class="flex min-h-[2.5rem] flex-wrap items-center gap-1.5 border-b border-gray-100 px-3 py-2">
        <template x-for="id in picked" :key="id">
            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                <span x-text="labels[id]"></span>
                <button type="button" @click="remove(id)" class="text-red-400 hover:text-red-700" :aria-label="'Hapus ' + labels[id]">&times;</button>
            </span>
        </template>
        <span x-show="picked.length === 0" class="text-xs text-gray-400">Belum ada bidang dipilih.</span>
        <span x-cloak x-show="max !== null" class="ml-auto text-xs text-gray-400"><span x-text="picked.length"></span>/<span x-text="max"></span></span>
    </div>

    {{-- Pencarian --}}
    <div class="border-b border-gray-100 px-3 py-2">
        <input type="search" x-model="q" autocomplete="off" placeholder="{{ $placeholder }}"
               class="w-full border-0 bg-transparent p-0 text-sm placeholder-gray-400 focus:outline-none focus:ring-0">
    </div>

    {{-- Seluruh opsi --}}
    <div class="grid max-h-64 grid-cols-1 gap-x-4 overflow-y-auto p-2 sm:grid-cols-2">
        @foreach ($options as $id => $label)
            <label x-show="show(@js($label))" class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 has-[:disabled]:cursor-not-allowed has-[:disabled]:text-gray-300">
                <input type="checkbox" name="{{ $name }}[]" value="{{ $id }}" x-model="picked"
                       :disabled="full && !picked.includes('{{ $id }}')" @checked(in_array((string) $id, $selected, true))
                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span>{{ $label }}</span>
            </label>
        @endforeach
        <p x-cloak x-show="q && !Object.values(labels).some(l => show(l))" class="px-2 py-1.5 text-sm text-gray-400 sm:col-span-2">Tidak ada hasil.</p>
    </div>
</div>
@endif
