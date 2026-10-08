{{-- Modal Profil & Riwayat Reviewer (read-only). --}}
<div x-data="reviewerModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'reviewer' && open($event.detail.id)">

    <x-editor.modal title="Profil Reviewer" subtitle="Bidang keahlian dan riwayat penugasan">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800" x-text="detail?.reviewer?.name"></h3>
                <p class="text-sm text-gray-500" x-text="detail?.reviewer?.email"></p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="detail?.is_busy ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                  x-text="detail?.is_busy ? 'Sibuk' : 'Tersedia'"></span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs text-gray-500">Naskah Ditangani (aktif)</p>
                <p class="text-2xl font-bold text-gray-900" x-text="detail?.active"></p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="text-xs text-gray-500">Review Selesai</p>
                <p class="text-2xl font-bold text-gray-900" x-text="detail?.completed"></p>
            </div>
        </div>

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Bidang Keahlian</p>
            <div class="flex flex-wrap gap-1.5">
                <template x-for="f in detail?.reviewer?.fields ?? []" :key="f">
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs" x-text="f"></span>
                </template>
                <span x-show="!(detail?.reviewer?.fields ?? []).length" class="text-xs text-gray-400">Belum diatur</span>
            </div>
        </div>

        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Riwayat Penugasan</p>
            <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                <template x-for="(h, i) in detail?.history ?? []" :key="i">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800 truncate" x-text="h.title"></p>
                            <p class="text-xs text-gray-400">
                                Ditugaskan <span x-text="h.assigned"></span>
                                <span x-show="h.recommendation_label">· <span x-text="h.recommendation_label"></span></span>
                                <span x-show="h.superseded">· digantikan reviewer lain</span>
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                              :class="h.status_badge_class" x-text="h.status_label"></span>
                    </div>
                </template>
                <p x-show="!(detail?.history ?? []).length" class="px-4 py-6 text-center text-sm text-gray-400">
                    Belum ada riwayat penugasan.
                </p>
            </div>
        </div>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function reviewerModal() {
    return editorModal({
        profileUrl: @js(route('editor.reviewers.profile', '__ID__')),
        open(id) { this.openModal(this.profileUrl.replace('__ID__', id)); },
    });
}
</script>
@endpush
