{{--
    Modal Detail & Penilaian Review (dibuka dengan openEditorModal('review-form', reviewId)).
    Prop: $reload (bool) — true bila dipakai di Dashboard (tabel statis): halaman dimuat ulang setelah aksi.
--}}
@php $reload = $reload ?? false; @endphp

<div x-data="reviewModal(@js($reload))"
     x-on:open-editor-modal.window="$event.detail.name === 'review-form' && open($event.detail.id)">

    <x-editor.modal width="max-w-4xl" title="Detail Penugasan Review" subtitle="Informasi naskah, penilaian, dan rekomendasi">

        {{-- INFORMASI NASKAH --}}
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h3 class="text-base font-bold text-gray-900 flex-1 min-w-0 break-words" x-text="r?.manuscript.title"></h3>
                <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="r?.status_class" x-text="r?.status_label"></span>
                    <span x-show="r?.is_overdue" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Terlambat</span>
                </div>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bidang</dt><dd class="text-gray-800 mt-0.5" x-text="r?.manuscript.field"></dd></div>
                <div><dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Diajukan</dt><dd class="text-gray-800 mt-0.5" x-text="r?.manuscript.submitted_at"></dd></div>
                <div><dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tgl Penugasan</dt><dd class="text-gray-800 mt-0.5" x-text="r?.assigned_at"></dd></div>
                <div><dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Batas Waktu</dt><dd class="text-gray-800 mt-0.5" x-text="r?.due_at || '—'"></dd></div>
            </dl>

            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Abstrak</p>
                <p class="text-sm text-gray-700 whitespace-pre-line" x-text="r?.manuscript.abstract || '—'"></p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Kata Kunci</p>
                <p class="text-sm text-gray-700" x-text="r?.manuscript.keywords || '—'"></p>
            </div>

            <div x-show="r?.manuscript.editor_note" class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-1">Catatan Editor</p>
                <p class="text-sm text-gray-700 whitespace-pre-line" x-text="r?.manuscript.editor_note"></p>
            </div>
        </div>

        <x-editor.file-preview label="Berkas Naskah" url="detail.review.manuscript.file_url" />

        <div x-show="r?.manuscript.revision_url">
            <x-editor.file-preview label="Berkas Revisi dari Author" url="detail.review.manuscript.revision_url" :preview="false" />
        </div>

        <hr class="border-gray-100">

        {{-- PENOLAKAN (read-only) --}}
        <div x-show="r?.decline_reason" class="rounded-xl border border-red-200 bg-red-50/60 p-4">
            <p class="text-xs font-semibold text-red-800 uppercase tracking-wider mb-1">Alasan Penolakan Anda</p>
            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="r?.decline_reason"></p>
        </div>

        {{-- TERIMA / TOLAK PENUGASAN --}}
        <div x-show="r?.can.respond" class="rounded-xl border border-gray-200 p-4 space-y-3">
            <p class="text-sm text-gray-700">Apakah Anda bersedia meninjau naskah ini? Menolak akan mengembalikan penugasan ke editor.</p>
            <div class="flex flex-wrap gap-2" x-show="!declining">
                <button type="button" @click="accept()" :disabled="submitting"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">Terima Penugasan</button>
                <button type="button" @click="declining = true" :disabled="submitting"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 transition-colors">Tolak Penugasan</button>
            </div>
            <div x-show="declining" class="space-y-2">
                <label class="block text-xs font-semibold text-gray-600" for="rv-reason">Alasan penolakan <span class="text-red-500">*</span></label>
                <textarea id="rv-reason" x-model="reason" rows="3" maxlength="1000"
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
                <div class="flex gap-2">
                    <button type="button" @click="decline()" :disabled="submitting"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">Kirim Penolakan</button>
                    <button type="button" @click="declining = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</button>
                </div>
            </div>
        </div>

        {{-- FORM PENILAIAN (kirim pertama kali atau ubah sebelum editor memutuskan) --}}
        <form x-show="r?.can.submit || r?.can.edit" @submit.prevent="save()" class="space-y-5">
            <p x-show="r?.can.edit" class="text-xs text-blue-700 bg-blue-50 rounded-lg px-3 py-2">
                Review sudah dikirim. Anda masih dapat memperbaikinya selama editor belum memutuskan.
            </p>

            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Rubrik Penilaian <span class="text-red-500">*</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="c in r?.rubric ?? []" :key="c.key">
                        <fieldset class="rounded-xl border border-gray-200 p-4">
                            <legend class="px-1 text-sm font-medium text-gray-800" x-text="c.label"></legend>
                            <div class="mt-1 space-y-1.5">
                                <template x-for="lv in r.levels" :key="lv">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" :name="c.key" :value="lv" x-model="form.scores[c.key]" class="h-4 w-4 accent-red-600">
                                        <span x-text="lv"></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>
                    </template>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rv-comments">
                    Komentar & Perbaikan untuk Author/Editor <span class="text-red-500">*</span>
                </label>
                <textarea id="rv-comments" x-model="form.comments" rows="5" maxlength="5000"
                          placeholder="Tulis komentar, kritik, dan saran perbaikan yang konstruktif..."
                          class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-y"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rv-rec">Rekomendasi Akhir <span class="text-red-500">*</span></label>
                    <select id="rv-rec" x-model="form.recommendation"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                        <option value="">— Pilih Rekomendasi —</option>
                        <template x-for="o in r?.recommendations ?? []" :key="o.value">
                            <option :value="o.value" x-text="o.label"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="rv-file">
                        Berkas Catatan Review <span class="font-normal text-gray-400">(opsional, PDF/DOCX maks. 10 MB)</span>
                    </label>
                    <input id="rv-file" type="file" x-ref="file" accept=".pdf,.docx" @change="file = $event.target.files[0] ?? null"
                           class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-white file:mr-3 file:py-2.5 file:px-4 file:border-0 file:bg-red-50 file:text-red-700 file:font-medium hover:file:bg-red-100">
                    <a x-show="r?.result?.file_url" :href="r?.result?.file_url" target="_blank" rel="noopener"
                       class="inline-block mt-1.5 text-xs font-medium text-red-600 hover:text-red-700">
                        Berkas saat ini: <span x-text="r?.result?.file_name"></span>
                    </a>
                </div>
            </div>
        </form>

        {{-- HASIL REVIEW (read-only, bila sudah selesai dan tidak dapat diubah lagi) --}}
        <div x-show="r?.result && !r?.can.edit" class="space-y-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Hasil Review Anda</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <template x-for="c in r?.rubric ?? []" :key="c.key">
                    <div class="rounded-lg border border-gray-200 p-3">
                        <p class="text-xs text-gray-500" x-text="c.label"></p>
                        <p class="text-sm font-semibold text-gray-800" x-text="r.result.scores[c.key] || '—'"></p>
                    </div>
                </template>
            </div>
            <p class="text-sm text-gray-700">Rekomendasi: <strong x-text="r?.result?.recommendation_label"></strong>
                <span class="text-gray-400" x-show="r?.result?.score_average" x-text="`· rata-rata skor ${r.result.score_average}/4`"></span></p>
            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="r?.result?.comments"></p>
            <a x-show="r?.result?.file_url" :href="r?.result?.file_url" target="_blank" rel="noopener"
               class="inline-block text-xs font-medium text-red-600 hover:text-red-700">Unduh berkas catatan review</a>
        </div>

        <x-slot:footer>
            <button type="button" x-show="r?.can.submit || r?.can.edit" @click="save()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Mengirim...' : (r?.can.edit ? 'Perbarui Review' : 'Kirim Review')"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function reviewModal(reload) {
    const blank = () => ({ scores: {}, comments: '', recommendation: '' });

    return editorModal({
        detailUrl: @js(route('reviewer.reviews.detail', '__ID__')),
        acceptUrl: @js(route('reviewer.reviews.accept', '__ID__')),
        declineUrl: @js(route('reviewer.reviews.decline', '__ID__')),
        submitUrl: @js(route('reviewer.reviews.submit', '__ID__')),
        updateUrl: @js(route('reviewer.reviews.update', '__ID__')),
        reviewId: null,
        form: blank(),
        file: null,
        declining: false,
        reason: '',

        r: null, // = detail.review (properti biasa: getter akan "beku" saat Object.assign di editorModal)

        // Dipanggil setelah detail dimuat: simpan ref dan isi form dari hasil yang sudah ada (mode ubah)
        onLoaded(d) {
            this.r = d.review;
            const res = d.review.result;
            this.form = res
                ? { scores: { ...res.scores }, comments: res.comments ?? '', recommendation: res.recommendation ?? '' }
                : blank();
        },

        open(id) {
            this.reviewId = id;
            this.r = null;
            this.declining = false;
            this.reason = '';
            this.file = null;
            this.form = blank();
            this.$nextTick(() => { if (this.$refs.file) this.$refs.file.value = ''; });
            this.openModal(this.detailUrl.replace('__ID__', id));
        },

        url(template) { return template.replace('__ID__', this.reviewId); },

        // Kirim aksi; segarkan tabel; tutup (atau muat ulang halaman di dashboard) bila selesai.
        async send(url, body, { keepOpen = false } = {}) {
            this.errorMsg = '';
            this.successMsg = '';
            this.submitting = true;
            try {
                const res = await window.editorApi(url, { method: 'POST', body });
                this.successMsg = res.message;
                window.dispatchEvent(new CustomEvent('table-refresh'));

                if (keepOpen) {
                    this.detail = await window.editorApi(this.url(this.detailUrl));
                    this.onLoaded(this.detail);
                } else {
                    setTimeout(() => (reload ? window.location.reload() : this.close()), 1000);
                }
                return res;
            } catch (e) {
                this.errorMsg = e.message;
                return null;
            } finally {
                this.submitting = false;
            }
        },

        accept() { return this.send(this.url(this.acceptUrl), {}, { keepOpen: true }); },

        decline() {
            if (this.reason.trim().length < 5) { this.errorMsg = 'Alasan penolakan minimal 5 karakter.'; return; }
            return this.send(this.url(this.declineUrl), { reason: this.reason });
        },

        save() {
            this.errorMsg = '';
            const missing = this.r.rubric.find(c => !this.form.scores[c.key]);
            if (missing) { this.errorMsg = `Nilai "${missing.label}" belum dipilih.`; return; }
            if (this.form.comments.trim().length < 10) { this.errorMsg = 'Komentar reviewer minimal 10 karakter.'; return; }
            if (!this.form.recommendation) { this.errorMsg = 'Pilih rekomendasi akhir.'; return; }
            if (this.file && this.file.size > 10 * 1024 * 1024) { this.errorMsg = 'Ukuran berkas maksimal 10 MB.'; return; }

            const body = new FormData();
            this.r.rubric.forEach(c => body.append(c.key, this.form.scores[c.key]));
            body.append('comments', this.form.comments);
            body.append('recommendation', this.form.recommendation);
            if (this.file) body.append('review_file', this.file);

            // Ubah = PUT lewat method spoofing (FormData tidak mendukung PUT multipart di PHP)
            if (this.r.can.edit) body.append('_method', 'PUT');

            return this.send(this.url(this.r.can.edit ? this.updateUrl : this.submitUrl), body);
        },
    });
}
</script>
@endpush
