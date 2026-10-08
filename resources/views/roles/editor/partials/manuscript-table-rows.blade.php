{{-- Baris tabel Naskah Baru (dirender ulang via AJAX). Prop: $rows --}}
@forelse ($rows as $manuscript)
<tr class="hover:bg-gray-50/60 transition-colors">
    @include('roles.editor.partials.manuscript-cells', ['manuscript' => $manuscript])
    <td class="px-6 py-4">
        <x-editor.action-button modal="manuscript" :id="$manuscript->id" label="Lihat Detail" />
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 4, 'message' => 'Tidak ada naskah baru yang sesuai.'])
@endforelse
