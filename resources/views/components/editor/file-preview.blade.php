{{--
    Pratinjau PDF + tombol unduh.
    Props: label, url (ekspresi Alpine, mis. "detail.manuscript.file_url"), preview (tampilkan iframe)
--}}
@props(['label', 'url', 'preview' => true])

<div>
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $label }}</p>
    @if ($preview)
        <div x-show="{{ $url }}" class="border border-gray-200 rounded-xl overflow-hidden bg-gray-100" style="height: 260px;">
            <iframe :src="{{ $url }}" class="w-full h-full" frameborder="0" title="{{ $label }}"></iframe>
        </div>
    @endif
    <div x-show="!{{ $url }}" class="border border-dashed border-gray-300 rounded-xl p-5 flex items-center justify-center text-gray-400 text-sm">
        Berkas tidak tersedia.
    </div>
    <div x-show="{{ $url }}" class="mt-2">
        <a :href="{{ $url }}" target="_blank" rel="noopener" download
           class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Unduh Berkas
        </a>
    </div>
</div>
