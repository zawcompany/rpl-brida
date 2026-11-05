{{-- Kartu jurnal: cover, nama, dan bidang. Props: journal, joined (tanda "Anggota"). --}}
@props(['journal', 'joined' => false])

<a href="{{ route('journals.show', ['journal' => $journal] + \App\Support\Nav::keep()) }}"
   class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
    <div class="relative">
        <x-public.journal-cover :journal="$journal" class="aspect-[3/4] w-full" />
        @if ($joined)
            <span class="absolute right-2 top-2 rounded-full bg-white/95 px-2.5 py-0.5 text-xs font-semibold text-green-700 shadow-sm">Anggota</span>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-bold leading-snug text-gray-900 group-hover:text-red-700">{{ $journal->name }}</h3>
        @if ($journal->field)
            <p class="mt-1 text-sm text-gray-500">{{ $journal->field }}</p>
        @endif
    </div>
</a>
