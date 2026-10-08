{{-- Baris tabel Naskah Saya. Prop: $rows --}}
@forelse ($rows as $manuscript)
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 max-w-[300px]">
        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $manuscript->title }}">{{ $manuscript->title }}</p>
    </td>
    <td class="px-6 py-4 text-sm text-gray-600">{{ $manuscript->researchField?->name ?? '—' }}</td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ ($manuscript->submitted_at ?? $manuscript->created_at)->format('d M Y') }}</td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-editor.badge :color="$manuscript->status_badge_class">{{ $manuscript->status_label }}</x-editor.badge>
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="tracking" :id="$manuscript->id" label="Detail / Tracking" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Belum ada naskah yang sesuai.'])
@endforelse
