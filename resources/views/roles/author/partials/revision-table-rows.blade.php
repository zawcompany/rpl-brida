{{-- Baris tabel Hasil Review & Revisi. Prop: $rows (naskah berstatus 'revisi') --}}
@forelse ($rows as $manuscript)
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 max-w-[260px]">
        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $manuscript->title }}">{{ $manuscript->title }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $manuscript->researchField?->name ?? '—' }}</p>
    </td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $manuscript->decided_at?->format('d M Y') ?? '—' }}</td>
    <td class="px-6 py-4 max-w-[320px]">
        <p class="text-sm text-gray-600 line-clamp-2">{{ $manuscript->editorial_note ?: 'Lihat rincian di modal.' }}</p>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-editor.badge :color="$manuscript->status_badge_class">{{ $manuscript->status_label }}</x-editor.badge>
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="revision" :id="$manuscript->id" label="Unggah Revisi" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Tidak ada naskah yang memerlukan revisi.'])
@endforelse
