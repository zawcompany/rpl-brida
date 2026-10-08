{{-- 3 kolom awal yang sama di tabel naskah: Naskah | Penulis | Tanggal. Prop: $manuscript --}}
<td class="px-6 py-4 max-w-[260px]">
    <p class="text-sm font-medium text-gray-800 truncate" title="{{ $manuscript->title }}">{{ $manuscript->title }}</p>
    <p class="text-xs text-gray-400 mt-0.5">{{ $manuscript->researchField?->name ?? 'Bidang tidak ditentukan' }}</p>
</td>
<td class="px-6 py-4">
    <p class="text-sm text-gray-700">{{ $manuscript->author?->name ?? '—' }}</p>
    <p class="text-xs text-gray-400">{{ $manuscript->author?->email ?? '' }}</p>
</td>
<td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $manuscript->created_at->format('d M Y') }}</td>
