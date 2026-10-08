{{--
    Partial: Baris tabel naskah baru — dirender ulang via AJAX.
    Prop: $manuscripts (LengthAwarePaginator)
--}}
@forelse ($manuscripts as $manuscript)
<tr class="hover:bg-gray-50/60 transition-colors group">
    <td class="px-6 py-4 max-w-[240px]">
        <p class="text-sm font-medium text-gray-800 truncate" title="{{ $manuscript->title }}">
            {{ $manuscript->title }}
        </p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $manuscript->researchField?->name ?? 'Bidang tidak ditentukan' }}</p>
    </td>
    <td class="px-6 py-4">
        <p class="text-sm text-gray-700">{{ $manuscript->author?->name ?? '—' }}</p>
        <p class="text-xs text-gray-400">{{ $manuscript->author?->email ?? '' }}</p>
    </td>
    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
        {{ $manuscript->created_at->format('d M Y') }}
    </td>
    <td class="px-6 py-4">
        <button type="button"
                onclick="openManuscriptModal({{ $manuscript->id }})"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Lihat Detail
        </button>
    </td>
</tr>
@empty
<tr>
    <td colspan="4" class="px-6 py-16 text-center">
        <div class="flex flex-col items-center gap-2 text-gray-400">
            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="text-sm">Tidak ada naskah yang sesuai dengan filter.</p>
        </div>
    </td>
</tr>
@endforelse
