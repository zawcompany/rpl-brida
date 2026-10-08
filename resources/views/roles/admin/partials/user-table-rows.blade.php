{{-- Baris tabel Kelola Pengguna. Prop: $rows --}}
@forelse ($rows as $user)
<tr class="hover:bg-gray-50/60 transition-colors">
    <td class="px-6 py-4 text-sm font-medium text-gray-800 whitespace-nowrap">{{ $user->name }}</td>
    <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
    <td class="px-6 py-4 text-sm text-gray-600">
        {{ $user->institution ?: '—' }}
        @if ($user->phone)<p class="text-xs text-gray-400">{{ $user->phone }}</p>@endif
    </td>
    <td class="px-6 py-4 whitespace-nowrap"><x-editor.badge :color="$user->role_badge_class">{{ $user->role_label }}</x-editor.badge></td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-editor.badge :color="$user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'">{{ $user->is_active ? 'Aktif' : 'Suspend' }}</x-editor.badge>
    </td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</td>
    <td class="px-6 py-4">
        <div class="flex flex-wrap gap-1.5">
            <x-admin.action modal="user-form" :id="$user->id" label="Edit" tone="primary" />
            @unless ($user->is(auth()->user()))
                <x-admin.action modal="user-toggle" :id="$user->id" :label="$user->is_active ? 'Suspend' : 'Aktifkan'" :tone="$user->is_active ? 'danger' : 'default'" />
            @endunless
            <x-admin.action modal="user-reset" :id="$user->id" label="Reset Password" />
        </div>
    </td>
</tr>
@empty
    @include('roles.editor.partials.empty-row', ['colspan' => 7, 'message' => 'Tidak ada pengguna yang sesuai.'])
@endforelse
