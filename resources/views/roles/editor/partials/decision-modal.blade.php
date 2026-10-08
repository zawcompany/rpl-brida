{{-- Modal Keputusan Editorial: rekap penilaian reviewer, berkas revisi author, form keputusan akhir. --}}
<div x-data="decisionModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'decision' && open($event.detail.id)">

    <x-editor.modal title="Keputusan Editorial" subtitle="Rekapitulasi review dan keputusan akhir">
        @include('roles.editor.partials.manuscript-info')

        {{-- Rekapitulasi penilaian reviewer --}}
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Penilaian Reviewer</p>
            <div class="space-y-3">
                <template x-for="r in detail?.reviews ?? []" :key="r.id">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-medium text-gray-800" x-text="r.reviewer_name"></p>
                                <p class="text-xs text-gray-400">Selesai <span x-text="r.completed_at ?? '—'"></span></p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="r.recommendation_class" x-text="r.recommendation_label ?? 'Tanpa rekomendasi'"></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line" x-text="r.comments || 'Tanpa catatan.'"></p>
                    </div>
                </template>
                <p x-show="!(detail?.reviews ?? []).length" class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">
                    Belum ada hasil review yang tercatat.
                </p>
            </div>
        </div>

        <x-editor.file-preview label="Berkas Naskah" url="detail.manuscript.file_url" :preview="false" />

        {{-- Berkas revisi dari author (jika ada) --}}
        <div x-show="detail?.manuscript?.revision_file_url">
            <x-editor.file-preview label="Berkas Revisi dari Author" url="detail.manuscript.revision_file_url" :preview="false" />
        </div>

        <hr class="border-gray-100">

        {{-- Naskah revisi dari author: pilih keputusan langsung (revisi minor) atau review ulang (revisi mayor) --}}
        <div x-show="detail?.manuscript?.is_revision" class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-3">
            <p class="text-sm text-blue-900">Ini adalah <strong>naskah revisi</strong> dari author. Pilih cara penanganan:</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start gap-2 p-3 bg-white border rounded-lg cursor-pointer" :class="mode === 'direct' ? 'border-red-500' : 'border-gray-200'">
                    <input type="radio" value="direct" x-model="mode" class="mt-0.5">
                    <span class="text-sm"><span class="font-medium text-gray-800">Keputusan Langsung</span><br><span class="text-xs text-gray-500">Periksa mandiri (revisi minor).</span></span>
                </label>
                <label class="flex items-start gap-2 p-3 bg-white border rounded-lg cursor-pointer" :class="mode === 'rereview' ? 'border-red-500' : 'border-gray-200'">
                    <input type="radio" value="rereview" x-model="mode" class="mt-0.5">
                    <span class="text-sm"><span class="font-medium text-gray-800">Kirim Review Ulang</span><br><span class="text-xs text-gray-500">Kembali ke reviewer (revisi mayor).</span></span>
                </label>
            </div>
        </div>

        {{-- Panel review ulang --}}
        <div x-show="mode === 'rereview'" class="space-y-4">
            @include('roles.editor.partials.recommendation-box')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="d-reviewer">Reviewer</label>
                <select id="d-reviewer" x-model="form.reviewer_id"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    <option value="">— Pilih Reviewer —</option>
                    <template x-for="r in detail?.reviewers ?? []" :key="r.id">
                        <option :value="r.id" x-text="`${r.name} (${r.active_load} naskah aktif)`"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="d-due">
                    Tenggat Review <span class="font-normal text-gray-400">(default 14 hari, dapat diubah)</span>
                </label>
                <input id="d-due" type="date" x-model="form.due_at" :min="new Date().toISOString().slice(0, 10)"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="d-rnote">Catatan untuk Reviewer <span class="font-normal text-gray-400">(opsional)</span></label>
                <textarea id="d-rnote" x-model="form.editor_note" rows="2"
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
        </div>

        <form x-show="mode === 'direct'" @submit.prevent="save()" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="d-decision">
                    Keputusan Akhir <span class="text-red-500">*</span>
                </label>
                <select id="d-decision" x-model="form.decision"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    <option value="">— Pilih Keputusan —</option>
                    <option value="diterima">Diterima (status: Disetujui)</option>
                    <option value="revisi">Minta Revisi (status: Perlu Revisi)</option>
                    <option value="ditolak">Ditolak (status: Ditolak)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="d-note">
                    Catatan Editorial
                    <span class="font-normal text-gray-400" x-text="form.decision && form.decision !== 'diterima' ? '(wajib)' : '(opsional)'"></span>
                </label>
                <textarea id="d-note" x-model="form.editorial_note" rows="4" placeholder="Tulis catatan untuk penulis..."
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" @click="save()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Menyimpan...' : (mode === 'rereview' ? 'Kirim Review Ulang' : 'Simpan Keputusan')"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function decisionModal() {
    return editorModal({
        detailUrl: @js(route('editor.decisions.detail', '__ID__')),
        storeUrl: @js(route('editor.decisions.store', '__ID__')),
        manuscriptId: null,
        mode: 'direct',
        rereviewUrl: @js(route('editor.decisions.rereview', '__ID__')),
        form: { decision: '', editorial_note: '', reviewer_id: '', due_at: '', editor_note: '' },

        onLoaded(d) { this.form.due_at = d.default_due_at; },

        useRecommendation() { this.form.reviewer_id = this.detail.recommendation.id; },

        open(id) {
            this.manuscriptId = id;
            this.mode = 'direct';
            this.form = { decision: '', editorial_note: '', reviewer_id: '', due_at: '', editor_note: '' };
            this.openModal(this.detailUrl.replace('__ID__', id));
        },

        save() {
            this.errorMsg = '';
            if (this.mode === 'rereview') {
                if (!this.form.reviewer_id) { this.errorMsg = 'Pilih reviewer untuk review ulang.'; return; }
                this.submit(this.rereviewUrl.replace('__ID__', this.manuscriptId), {
                    reviewer_id: this.form.reviewer_id, due_at: this.form.due_at, editor_note: this.form.editor_note,
                });
                return;
            }
            if (!this.form.decision) { this.errorMsg = 'Pilih keputusan akhir terlebih dahulu.'; return; }
            if (this.form.decision !== 'diterima' && !this.form.editorial_note.trim()) {
                this.errorMsg = 'Catatan editorial wajib diisi untuk revisi atau penolakan.';
                return;
            }
            this.submit(this.storeUrl.replace('__ID__', this.manuscriptId), this.form);
        },
    });
}
</script>
@endpush
