{{-- Modal Unggah Revisi: catatan editorial & komentar reviewer (read-only), berkas revisi, surat tanggapan. --}}
<div x-data="revisionModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'revision' && open($event.detail.id)">

    <x-editor.modal title="Unggah Revisi Naskah" subtitle="Tinjau catatan, lalu kirim revisi final beserta tanggapan Anda">
        <div>
            <h3 class="text-base font-bold text-gray-900 break-words" x-text="detail?.manuscript?.title"></h3>
            <p class="text-xs text-gray-400 mt-0.5" x-text="detail?.manuscript?.field"></p>
        </div>

        {{-- Catatan editorial --}}
        <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-4">
            <p class="text-xs font-semibold text-orange-800 uppercase tracking-wider mb-1">
                Catatan Editorial <span class="font-normal normal-case" x-show="detail?.manuscript?.decided_at" x-text="`· ${detail.manuscript.decided_at}`"></span>
            </p>
            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="detail?.manuscript?.editorial_note || 'Tidak ada catatan tertulis dari editor.'"></p>
        </div>

        {{-- Rekapitulasi komentar reviewer (anonim) --}}
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rekapitulasi Komentar Reviewer</p>
            <div class="space-y-3">
                <template x-for="r in detail?.reviews ?? []" :key="r.label">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-medium text-gray-800" x-text="r.label"></p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  x-show="r.recommendation" :class="r.recommendation_class" x-text="r.recommendation"></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line" x-text="r.comments"></p>
                    </div>
                </template>
                <p x-show="!(detail?.reviews ?? []).length" class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">
                    Tidak ada komentar reviewer yang diteruskan.
                </p>
            </div>
        </div>

        <a :href="detail?.manuscript?.file_url"
           class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-700">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Unduh naskah yang diajukan
        </a>

        <hr class="border-gray-100">

        {{-- Form re-submission --}}
        <form @submit.prevent="send()" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rev-file">
                    Berkas Revisi Final <span class="text-red-500">*</span>
                    <span class="font-normal text-gray-400">(PDF/DOCX, maks. 10 MB)</span>
                </label>
                <input id="rev-file" type="file" x-ref="file" accept=".pdf,.docx" @change="file = $event.target.files[0] ?? null"
                       class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-white file:mr-3 file:py-2.5 file:px-4 file:border-0 file:bg-red-50 file:text-red-700 file:font-medium hover:file:bg-red-100">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rev-response">
                    Catatan Tanggapan Penulis (Author Response Letter) <span class="text-red-500">*</span>
                </label>
                <textarea id="rev-response" x-model="response" rows="5" maxlength="5000"
                          placeholder="Jelaskan poin-poin perbaikan yang telah Anda lakukan terhadap setiap catatan di atas..."
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" @click="send()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Mengunggah...' : 'Kirim Revisi'"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function revisionModal() {
    return editorModal({
        detailUrl: @js(route('author.revisions.detail', '__ID__')),
        uploadUrl: @js(route('author.revisions.upload', '__ID__')),
        manuscriptId: null,
        file: null,
        response: '',

        open(id) {
            this.manuscriptId = id;
            this.file = null;
            this.response = '';
            this.$nextTick(() => { if (this.$refs.file) this.$refs.file.value = ''; });
            this.openModal(this.detailUrl.replace('__ID__', id));
        },

        async send() {
            this.errorMsg = '';
            if (!this.file) { this.errorMsg = 'Pilih berkas revisi terlebih dahulu.'; return; }
            if (this.file.size > 10 * 1024 * 1024) { this.errorMsg = 'Ukuran berkas revisi maksimal 10 MB.'; return; }
            if (!this.response.trim()) { this.errorMsg = 'Catatan tanggapan penulis wajib diisi.'; return; }

            const form = new FormData();
            form.append('revision_file', this.file);
            form.append('author_response', this.response);

            this.submitting = true;
            try {
                const res = await authorUpload(this.uploadUrl.replace('__ID__', this.manuscriptId), form);
                this.successMsg = res.message;
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('table-refresh'));
                    this.close();
                }, 1200);
            } catch (e) {
                this.errorMsg = e.message;
            } finally {
                this.submitting = false;
            }
        },
    });
}
</script>
@endpush
