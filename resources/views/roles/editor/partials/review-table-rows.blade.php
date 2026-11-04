{{-- Baris tabel Peninjauan Naskah. Prop: $rows --}}
@forelse ($rows as $manuscript)
@php $progress = $manuscript->review_progress; @endphp
<tr class="hover:bg-gray-50/60 transition-colors">
    @include('roles.editor.partials.manuscript-cells', ['manuscript' => $manuscript])
    <td class="px-6 py-4 whitespace-nowrap">
        @if ($manuscript->currentReviews->isNotEmpty())
            <x-editor.badge :color="$progress['complete'] ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                {{ $progress['done'] }}/{{ $progress['required'] }} selesai
            </x-editor.badge>
            @if ($manuscript->currentReviews->contains(fn ($r) => $r->isOverdue()))
                <x-editor.badge color="bg-red-50 text-red-600" class="ml-1">Terlambat</x-editor.badge>
            @endif
        @else
            <span class="text-xs text-gray-400">—</span>
        @endif
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="review" :id="$manuscript->id" label="Detail Review" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Tidak ada naskah dalam peninjauan.'])
@endforelse
