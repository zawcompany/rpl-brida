{{--
    Pratinjau PDF + tombol unduh.
    Props: label, url (ekspresi Alpine, mis. "detail.manuscript.file_url"), preview (tampilkan iframe),
           name (ekspresi Alpine berisi nama berkas asli; pratinjau hanya untuk .pdf).
    Iframe hanya dibuat untuk PDF: browser tidak dapat menampilkan DOCX di iframe dan malah mengunduhnya
    setiap kali modal dibuka.
--}}
@props(['label', 'url', 'preview' => true, 'name' => "''"])

<div>
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $label }}</p>
    @if ($preview)
        <template x-if="{{ $url }} && /\.pdf$/i.test({{ $name }} || '')">
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-100" style="height: 260px;">
                <iframe :src="{{ $url }}" class="w-full h-full" frameborder="0" title="{{ $label }}"></iframe>
            </div>
        </template>
        <div x-show="{{ $url }} && !/\.pdf$/i.test({{ $name }} || '')"
             class="border border-dashed border-gray-300 rounded-xl p-4 text-center text-gray-500 text-sm">
            Pratinjau hanya tersedia untuk berkas PDF. Gunakan tombol <strong>Unduh Berkas</strong> untuk membacanya.
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
