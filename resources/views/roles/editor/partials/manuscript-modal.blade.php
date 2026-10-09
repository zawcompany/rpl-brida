{{-- Modal Detail Naskah Baru: info read-only, rekomendasi 1 reviewer, form keputusan administrasi. --}}
<div x-data="manuscriptModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'manuscript' && open($event.detail.id)">

    <x-editor.modal title="Detail Naskah" subtitle="Pemeriksaan administrasi & penugasan reviewer">
        @include('roles.editor.partials.manuscript-info', ['full' => true])

        <x-editor.file-preview label="Berkas Naskah" url="detail.manuscript.file_url" />

        <hr class="border-gray-100">

        @include('roles.editor.partials.recommendation-box')

        <form @submit.prevent="save()" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="m-reviewer">Reviewer yang Ditugaskan</label>
                <select id="m-reviewer" x-model="form.reviewer_id" :disabled="form.decision === 'ditolak'"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white disabled:bg-gray-50 disabled:text-gray-400">
                    <option value="">— Pilih Reviewer —</option>
                    <template x-for="r in detail?.reviewers ?? []" :key="r.id">
                        <option :value="r.id" x-text="`${r.name} (${r.active_load} naskah aktif)`"></option>
                    </template>
                </select>
            </div>


            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="m-due">
                    Tenggat Review <span class="font-normal text-gray-400">(default 14 hari, dapat diubah)</span>
                </label>
                <input id="m-due" type="date" x-model="form.due_at" :disabled="form.decision === 'ditolak'" :min="new Date().toISOString().slice(0, 10)"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="m-note">
                    Catatan <span class="font-normal text-gray-400">(opsional)</span>
                </label>
                <textarea id="m-note" x-model="form.editor_note" rows="3" placeholder="Tulis catatan untuk reviewer..."
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="m-decision">
                    Keputusan Administrasi <span class="text-red-500">*</span>
                </label>
                <select id="m-decision" x-model="form.decision"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    <option value="">— Pilih Keputusan —</option>
                    <option value="diterima">Terima (lanjut ke review)</option>
                    <option value="ditolak">Tolak</option>
                </select>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" @click="save()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Mengirim...' : 'Kirim Keputusan'"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function manuscriptModal() {
    return editorModal({
        detailUrl: @js(route('editor.manuscripts.detail', '__ID__')),
        assignUrl: @js(route('editor.manuscripts.assign', '__ID__')),
        manuscriptId: null,
        form: { decision: '', reviewer_id: '', editor_note: '', due_at: '' },

        // Tenggat terisi otomatis (hari ini + 14 hari) dari server; editor bebas mengubahnya.
        onLoaded(d) { this.form.due_at = d.default_due_at; },

        open(id) {
            this.manuscriptId = id;
            this.form = { decision: '', reviewer_id: '', editor_note: '', due_at: '' };
            this.openModal(this.detailUrl.replace('__ID__', id));
        },

        // "Gunakan Rekomendasi Ini": isi dropdown reviewer + pilih "Terima".
        useRecommendation() {
            this.form.reviewer_id = this.detail.recommendation.id;
            this.form.decision = 'diterima';
        },

        save() {
            this.errorMsg = '';
            if (!this.form.decision) { this.errorMsg = 'Pilih keputusan administrasi terlebih dahulu.'; return; }
            if (this.form.decision === 'diterima' && !this.form.reviewer_id) {
                this.errorMsg = 'Pilih reviewer untuk meneruskan naskah ke tahap review.';
                return;
            }
            this.submit(this.assignUrl.replace('__ID__', this.manuscriptId), this.form);
        },
    });
}
</script>
@endpush
