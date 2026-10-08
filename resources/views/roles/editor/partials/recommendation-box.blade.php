{{--
    Box "Rekomendasi Sistem": TEPAT 1 reviewer terbaik (bidang cocok + beban aktif paling sedikit).
    Butuh scope Alpine: detail.recommendation dan method useRecommendation().
--}}
<div>
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rekomendasi Sistem</p>

    <template x-if="detail?.recommendation">
        <div class="flex items-center gap-3 p-3 rounded-lg border bg-green-50 border-green-200">
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-green-200 text-green-800 flex items-center justify-center">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800" x-text="detail.recommendation.name"></p>
                <p class="text-xs text-gray-500 mt-0.5">
                    Bidang keahlian sesuai · <span x-text="detail.recommendation.active_load"></span> naskah aktif (paling sedikit)
                </p>
            </div>
            <button type="button" @click="useRecommendation()"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition-colors">
                Gunakan Rekomendasi Ini
            </button>
        </div>
    </template>

    <p x-show="!detail?.recommendation" class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
        Belum ada reviewer dengan bidang keahlian yang sesuai. Pilih reviewer secara manual.
    </p>
</div>
