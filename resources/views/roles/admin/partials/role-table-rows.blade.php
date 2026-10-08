{{-- Baris tabel Role & Hak Akses. Prop: $rows --}}
@forelse ($rows as $user)
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 text-sm font-medium text-gray-800 whitespace-nowrap">{{ $user->name }}</td>
    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
    <td class="px-6 py-4 whitespace-nowrap"><x-editor.badge :color="$user->role_badge_class">{{ $user->role_label }}</x-editor.badge></td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-editor.badge :color="$user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'">{{ $user->is_active ? 'Aktif' : 'Suspend' }}</x-editor.badge>
    </td>
    <td class="px-6 py-4"><x-admin.action modal="user-role" :id="$user->id" label="Ubah Role" tone="primary" /></td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 5, 'message' => 'Tidak ada pengguna yang sesuai.'])
@endforelse
