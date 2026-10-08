{{-- Baris tabel Direktori Reviewer. Prop: $rows (User + active_load + researchFields) --}}
@forelse ($rows as $reviewer)
@php $busy = $reviewer->active_load >= \App\Models\User::MAX_ACTIVE_REVIEWS; @endphp
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $reviewer->name }}</td>
    <td class="px-6 py-4 text-sm text-gray-600">{{ $reviewer->email }}</td>
    <td class="px-6 py-4">
        <div class="flex flex-wrap gap-1">
            @forelse ($reviewer->researchFields as $field)
                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">{{ $field->name }}</span>
            @empty
                <span class="text-xs text-gray-400">—</span>
            @endforelse
        </div>
    </td>
    <td class="px-6 py-4 text-sm text-gray-700">{{ $reviewer->active_load }} naskah</td>
    <td class="px-6 py-4">
        <x-editor.badge :color="$busy ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'">{{ $busy ? 'Sibuk' : 'Tersedia' }}</x-editor.badge>
    </td>
    <td class="px-6 py-4">
        <x-editor.action-button modal="reviewer" :id="$reviewer->id" label="Lihat Profil" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 6, 'message' => 'Tidak ada reviewer yang sesuai.'])
@endforelse
