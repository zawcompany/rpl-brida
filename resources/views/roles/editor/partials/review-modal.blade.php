{{-- Modal Detail Peninjauan: progres 3 reviewer, tenggat, status, pengingat & ganti reviewer. --}}
<div x-data="reviewModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'review' && open($event.detail.id)">

    <x-editor.modal title="Detail Peninjauan" subtitle="Progres review dan tindak lanjut editor">
        @include('roles.editor.partials.manuscript-info')

        {{-- Indikator kelengkapan review, mis. "2 dari 3 reviewer telah selesai" --}}
        <div x-show="detail?.progress" class="rounded-xl border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Progres Review</p>
                <p class="text-sm font-medium text-gray-800" x-text="detail?.progress?.label"></p>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full transition-all"
                     :style="`width: ${((detail?.progress?.done ?? 0) / (detail?.progress?.required || 1)) * 100}%`"></div>
            </div>
        </div>

        <div class="space-y-3">
            <template x-for="(rv, i) in detail?.reviews ?? []" :key="rv.id">
                <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1" x-text="`Reviewer ${i + 1}`"></p>
                            <p class="text-sm font-medium text-gray-800" x-text="rv.reviewer_name"></p>
                            <p class="text-xs text-gray-400" x-text="rv.reviewer_email"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Tenggat Waktu</p>
                            <p class="text-sm font-medium text-gray-800" x-text="rv.due_at ?? '—'"></p>
                            <p x-show="rv.is_overdue" class="text-xs font-medium text-red-600">Terlambat</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status Review</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                  :class="rv.status_badge_class" x-text="rv.status_label"></span>
                        </div>
                    </div>
                    <div class="px-4 py-3 text-xs text-gray-500" x-show="rv.reminder_count > 0">
                        Pengingat terkirim <span x-text="rv.reminder_count"></span>×, terakhir
                        <span x-text="rv.reminded_at"></span>.
                    </div>
                    <div class="p-4" x-show="rv.status === 'selesai'">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Hasil Review</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="rv.recommendation_class" x-text="rv.recommendation_label"></span>
                        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line" x-text="rv.comments || 'Tanpa catatan.'"></p>
                        <p x-show="rv.notes_for_editor" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 whitespace-pre-line">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-amber-700">Catatan rahasia untuk editor</span>
                            <span x-text="rv.notes_for_editor"></span>
                        </p>
                    </div>
                    <div class="px-4 py-3" x-show="rv.can_replace">
                        <button type="button" @click="startReplace(rv.id)"
                                class="px-3 py-1.5 text-xs font-medium text-orange-700 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
                            Ganti Reviewer Ini
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Panel Ganti Reviewer (hanya untuk reviewer yang menolak / terlambat) --}}
        <div x-show="form.review_id" class="space-y-4 rounded-xl border border-orange-200 bg-orange-50/40 p-4">
            @include('roles.editor.partials.recommendation-box')

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="r-reviewer">Reviewer Pengganti</label>
                <select id="r-reviewer" x-model="form.reviewer_id"
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    <option value="">— Pilih Reviewer —</option>
                    <template x-for="r in detail?.reviewers ?? []" :key="r.id">
                        <option :value="r.id" x-text="`${r.name} (${r.active_load} naskah aktif)`"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="r-due">
                    Tenggat Review <span class="font-normal text-gray-400">(default 14 hari, dapat diubah)</span>
                </label>
                <input id="r-due" type="date" x-model="form.due_at" :min="new Date().toISOString().slice(0, 10)"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="r-note">
                    Catatan <span class="font-normal text-gray-400">(opsional)</span>
                </label>
                <textarea id="r-note" x-model="form.editor_note" rows="2"
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" x-show="(detail?.reviews ?? []).some(r => r.can_remind)" @click="remind()" :disabled="submitting || !!successMsg"
                    class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 disabled:opacity-50 transition-colors">
                Kirim Pengingat
            </button>
            <button type="button" x-show="form.review_id" @click="replace()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">
                Simpan Penggantian
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function reviewModal() {
    return editorModal({
        detailUrl: @js(route('editor.reviews.detail', '__ID__')),
        remindUrl: @js(route('editor.reviews.remind', '__ID__')),
        changeUrl: @js(route('editor.reviews.change', '__ID__')),
        manuscriptId: null,
        form: { review_id: '', reviewer_id: '', editor_note: '', due_at: '' },

        onLoaded(d) { this.form.due_at = d.default_due_at; },

        open(id) {
            this.manuscriptId = id;
            this.form = { review_id: '', reviewer_id: '', editor_note: '', due_at: '' };
            this.openModal(this.detailUrl.replace('__ID__', id));
        },

        // Penggantian satu slot reviewer: rekomendasi hanya 1 kandidat teratas.
        useRecommendation() { this.form.reviewer_id = String(this.detail.recommendations[0].id); },

        startReplace(reviewId) { this.form.review_id = reviewId; },

        remind() { this.submit(this.remindUrl.replace('__ID__', this.manuscriptId)); },

        replace() {
            this.errorMsg = '';
            if (!this.form.reviewer_id) { this.errorMsg = 'Pilih reviewer pengganti.'; return; }
            this.submit(this.changeUrl.replace('__ID__', this.manuscriptId), this.form);
        },
    });
}
</script>
@endpush
