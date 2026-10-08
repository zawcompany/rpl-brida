{{--
    Modal Detail Naskah & Form Penugasan Reviewer
    Dikontrol oleh Alpine.js (x-data), dibuka via CustomEvent 'open-manuscript-modal'.
    Reusable: bisa di-include dari dashboard maupun halaman naskah baru.
--}}
<div
    x-data="manuscriptModal()"
    x-on:open-manuscript-modal.window="open($event.detail.id)"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

    {{-- Modal Panel --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Detail Naskah</h2>
                <p class="text-xs text-gray-400 mt-0.5">Pemeriksaan administrasi & penugasan reviewer</p>
            </div>
            <button @click="close()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Loading state --}}
        <div x-show="loading" class="flex-1 flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3 text-gray-400">
                <svg class="animate-spin w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="text-sm">Memuat data naskah...</span>
            </div>
        </div>

        {{-- Modal Body (konten setelah data dimuat) --}}
        <div x-show="!loading && manuscript" class="flex-1 overflow-y-auto">

            {{-- Info Naskah (Read-only) --}}
            <div class="px-6 pt-5 pb-4 space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 leading-snug" x-text="manuscript?.title"></h3>
                    <p class="text-xs text-gray-400 mt-1">
                        Bidang: <span x-text="manuscript?.research_field?.name ?? '—'"></span>
                        &nbsp;·&nbsp;
                        Penulis: <span x-text="manuscript?.author?.name ?? '—'"></span>
                        &nbsp;·&nbsp;
                        Masuk: <span x-text="manuscript?.created_at ? new Date(manuscript.created_at).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'}) : '—'"></span>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Abstrak</p>
                        <p class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-3 max-h-28 overflow-y-auto"
                           x-text="manuscript?.abstract || 'Tidak tersedia'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Kata Kunci</p>
                        <div class="flex flex-wrap gap-1.5 bg-gray-50 rounded-lg p-3 min-h-[4rem]">
                            <template x-if="manuscript?.keywords">
                                <template x-for="kw in (manuscript?.keywords || '').split(',').map(s=>s.trim()).filter(Boolean)" :key="kw">
                                    <span class="px-2 py-0.5 bg-white border border-gray-200 rounded-full text-xs text-gray-600" x-text="kw"></span>
                                </template>
                            </template>
                            <template x-if="!manuscript?.keywords">
                                <span class="text-xs text-gray-400">Tidak tersedia</span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- PDF Preview --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Berkas Naskah</p>
                    <div x-show="fileUrl" class="border border-gray-200 rounded-xl overflow-hidden bg-gray-100" style="height: 260px;">
                        <iframe :src="fileUrl" class="w-full h-full" frameborder="0"></iframe>
                    </div>
                    <div x-show="!fileUrl" class="border border-dashed border-gray-300 rounded-xl p-6 flex items-center justify-center text-gray-400 text-sm">
                        Berkas PDF tidak tersedia.
                    </div>
                    <div x-show="fileUrl" class="mt-2">
                        <a :href="fileUrl" target="_blank" download
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600 hover:text-red-700 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Unduh Berkas
                        </a>
                    </div>
                </div>

                {{-- Garis pemisah --}}
                <hr class="border-gray-100">

                {{-- ============================================================ --}}
                {{-- REKOMENDASI SISTEM --}}
                {{-- ============================================================ --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Rekomendasi Sistem
                    </p>
                    <template x-if="recommendations.length > 0">
                        <div class="space-y-2">
                            <template x-for="(rec, idx) in recommendations.slice(0, 3)" :key="rec.id">
                                <div class="flex items-center gap-3 p-3 rounded-lg border"
                                     :class="idx === 0 ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'">
                                    <div class="flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                                         :class="idx === 0 ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-600'">
                                        <span x-text="idx + 1"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800" x-text="rec.name"></p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            <span x-text="rec.active_load"></span> naskah aktif
                                            <template x-if="rec.matched">
                                                <span class="ml-1.5 inline-flex items-center gap-1 text-green-700">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    Bidang sesuai
                                                </span>
                                            </template>
                                        </p>
                                    </div>
                                    <button type="button"
                                            @click="selectReviewer(rec.id)"
                                            class="flex-shrink-0 px-3 py-1 text-xs font-medium rounded-lg border transition-colors"
                                            :class="form.reviewer_id == rec.id ? 'bg-green-600 text-white border-green-600' : 'border-gray-300 text-gray-600 hover:border-red-400 hover:text-red-600'">
                                        <span x-text="form.reviewer_id == rec.id ? 'Dipilih ✓' : 'Pilih'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="recommendations.length === 0">
                        <p class="text-sm text-gray-400 bg-gray-50 rounded-lg p-3">Belum ada reviewer terdaftar.</p>
                    </template>
                </div>

                {{-- ============================================================ --}}
                {{-- FORM PENUGASAN --}}
                {{-- ============================================================ --}}
                <form @submit.prevent="submitDecision()" class="space-y-4">
                    {{-- CSRF --}}
                    <input type="hidden" name="_token" :value="csrfToken">

                    {{-- Dropdown semua reviewer --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reviewer yang Ditugaskan</label>
                        <select x-model="form.reviewer_id"
                                :disabled="form.decision === 'ditolak'"
                                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white disabled:bg-gray-50 disabled:text-gray-400 transition-colors">
                            <option value="">— Pilih Reviewer —</option>
                            <template x-for="r in allReviewers" :key="r.id">
                                <option :value="r.id" x-text="r.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Catatan opsional --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Catatan ke Reviewer <span class="font-normal text-gray-400">(opsional)</span></label>
                        <textarea x-model="form.editor_note" rows="3"
                                  placeholder="Tulis pesan khusus untuk reviewer..."
                                  class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none transition-colors"></textarea>
                    </div>

                    {{-- Keputusan Administrasi --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Keputusan Administrasi <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" x-model="form.decision" value="ditinjau" class="sr-only peer">
                                <div class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl text-sm font-medium transition-all
                                            peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-800
                                            border-gray-200 text-gray-600 hover:border-gray-300">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Lanjut ke Review
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" x-model="form.decision" value="ditolak" class="sr-only peer">
                                <div class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl text-sm font-medium transition-all
                                            peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-800
                                            border-gray-200 text-gray-600 hover:border-gray-300">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Desk Reject
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Error message --}}
                    <div x-show="errorMsg" x-text="errorMsg"
                         class="px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

                    {{-- Success message --}}
                    <div x-show="successMsg" x-text="successMsg"
                         class="px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg"></div>
                </form>
            </div>
        </div>

        {{-- Modal Footer (sticky) --}}
        <div x-show="!loading && manuscript"
             class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
            <button type="button" @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Batal
            </button>
            <button type="button" @click="submitDecision()"
                    :disabled="submitting || !form.decision"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                <svg x-show="submitting" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="submitting ? 'Mengirim...' : 'Kirim Keputusan'"></span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function manuscriptModal() {
    return {
        isOpen: false,
        loading: false,
        submitting: false,
        manuscript: null,
        recommendations: [],
        allReviewers: [],
        fileUrl: null,
        errorMsg: '',
        successMsg: '',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        form: {
            decision: '',
            reviewer_id: '',
            editor_note: '',
        },

        open(manuscriptId) {
            this.reset();
            this.isOpen = true;
            this.loading = true;
            this.fetchDetail(manuscriptId);
        },

        close() {
            this.isOpen = false;
            setTimeout(() => this.reset(), 300);
        },

        reset() {
            this.manuscript = null;
            this.recommendations = [];
            this.allReviewers = [];
            this.fileUrl = null;
            this.errorMsg = '';
            this.successMsg = '';
            this.loading = false;
            this.submitting = false;
            this.form = { decision: '', reviewer_id: '', editor_note: '' };
        },

        selectReviewer(id) {
            this.form.reviewer_id = id;
            // Otomatis pilih "Lanjut ke Review" kalau belum dipilih
            if (!this.form.decision) this.form.decision = 'ditinjau';
        },

        async fetchDetail(id) {
            try {
                const res = await fetch(`/editor/naskah/${id}/detail`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Gagal memuat data naskah.');
                const data = await res.json();
                this.manuscript      = data.manuscript;
                this.recommendations = data.recommendations;
                this.allReviewers    = data.all_reviewers;
                this.fileUrl         = data.file_url;
            } catch (err) {
                this.errorMsg = err.message;
            } finally {
                this.loading = false;
            }
        },

        async submitDecision() {
            this.errorMsg = '';
            this.successMsg = '';

            if (!this.form.decision) {
                this.errorMsg = 'Pilih keputusan administrasi terlebih dahulu.';
                return;
            }
            if (this.form.decision === 'ditinjau' && !this.form.reviewer_id) {
                this.errorMsg = 'Pilih reviewer untuk meneruskan naskah ke tahap review.';
                return;
            }

            this.submitting = true;
            try {
                const res = await fetch(`/editor/naskah/${this.manuscript.id}/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    this.errorMsg = data.message || 'Terjadi kesalahan.';
                    return;
                }

                this.successMsg = data.message;
                // Refresh tabel setelah 1.5 detik lalu tutup modal
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('manuscript-processed'));
                    this.close();
                }, 1500);

            } catch (err) {
                this.errorMsg = 'Koneksi gagal. Coba lagi.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush
