{{-- Info naskah read-only di dalam modal. Butuh scope Alpine: detail.manuscript. Opsi: $full (abstrak + kata kunci) --}}
@php $full = $full ?? false; @endphp
<div>
    <h3 class="text-base font-semibold text-gray-800 leading-snug" x-text="detail?.manuscript?.title"></h3>
    <p class="text-xs text-gray-400 mt-1">
        Bidang: <span x-text="detail?.manuscript?.field_name ?? '—'"></span>
        &nbsp;·&nbsp; Penulis: <span x-text="detail?.manuscript?.author_name ?? '—'"></span>
        &nbsp;·&nbsp; Masuk: <span x-text="detail?.manuscript?.date ?? '—'"></span>
    </p>
</div>

@if ($full)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Abstrak</p>
        <p class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto"
           x-text="detail?.manuscript?.abstract || 'Tidak tersedia'"></p>
    </div>
    <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kata Kunci</p>
        <div class="flex flex-wrap gap-1.5 bg-gray-50 rounded-lg p-3 min-h-[4rem]">
            <template x-for="kw in (detail?.manuscript?.keywords || '').split(',').map(s => s.trim()).filter(Boolean)" :key="kw">
                <span class="px-2 py-0.5 bg-white border border-gray-200 rounded-full text-xs text-gray-600" x-text="kw"></span>
            </template>
            <span x-show="!detail?.manuscript?.keywords" class="text-xs text-gray-400">Tidak tersedia</span>
        </div>
    </div>
</div>
@endif
