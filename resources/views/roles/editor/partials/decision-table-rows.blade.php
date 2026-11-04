{{-- Baris tabel Keputusan Editorial. Prop: $rows --}}
@forelse ($rows as $manuscript)
@php $progress = $manuscript->review_progress; @endphp
<tr class="hover:bg-gray-50/60 transition-colors">
    @include('roles.editor.partials.manuscript-cells', ['manuscript' => $manuscript])
    <td class="px-6 py-4 whitespace-nowrap">
        @if ($manuscript->currentReviews->isNotEmpty())
            <x-editor.badge color="bg-green-100 text-green-800">{{ $progress['done'] }}/{{ $progress['required'] }} selesai</x-editor.badge>
            @if ($manuscript->isRevision())
                <x-editor.badge color="bg-blue-50 text-blue-700" class="ml-1">Revisi</x-editor.badge>
            @endif
        @else
            <span class="text-xs text-gray-400">—</span>
        @endif
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="decision" :id="$manuscript->id" label="Buat Keputusan" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Tidak ada naskah yang menunggu keputusan.'])
@endforelse
