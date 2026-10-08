{{-- Baris tabel Peninjauan Naskah. Prop: $rows --}}
@forelse ($rows as $manuscript)
@php $review = $manuscript->currentReview; @endphp
<tr class="hover:bg-gray-50/60 transition-colors">
    @include('roles.editor.partials.manuscript-cells', ['manuscript' => $manuscript])
    <td class="px-6 py-4 whitespace-nowrap">
        @if ($review)
            <x-editor.badge :color="$review->status_badge_class">{{ $review->status_label }}</x-editor.badge>
            @if ($review->isOverdue())
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
