{{-- Baris tabel penugasan reviewer (Naskah Ditugaskan, Selesai, Dashboard). Prop: $rows (Review + manuscript.researchField) --}}
@forelse ($rows as $review)
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 max-w-[300px]">
        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $review->manuscript->title }}">{{ $review->manuscript->title }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $review->manuscript->researchField?->name ?? '—' }}</p>
    </td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $review->created_at->format('d M Y') }}</td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $review->due_at?->format('d M Y') ?? '—' }}</td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-editor.badge :color="$review->status_badge_class">{{ $review->status_label }}</x-editor.badge>
        @if ($review->isOverdue())
            <x-editor.badge color="bg-red-50 text-red-600" class="ml-1">Terlambat</x-editor.badge>
        @endif
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="review-form" :id="$review->id" :label="$review->isActive() ? 'Lanjutkan Review' : 'Lihat Detail'" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Belum ada penugasan review yang sesuai.'])
@endforelse
