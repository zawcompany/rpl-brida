{{-- Modal Detail Tracking: metadata read-only, unduhan berkas, dan timeline progres naskah. --}}
<div x-data="trackingModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'tracking' && open($event.detail.id)">

    <x-editor.modal title="Detail & Tracking Naskah" subtitle="Metadata dan progres naskah Anda">
        {{-- Metadata (read-only) --}}
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h3 class="text-base font-bold text-gray-900 flex-1 min-w-0 break-words" x-text="detail?.manuscript?.title"></h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="detail?.manuscript?.status_class" x-text="detail?.manuscript?.status_label"></span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Bidang Keahlian</dt>
                    <dd class="text-gray-800 mt-0.5" x-text="detail?.manuscript?.field"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Submit</dt>
                    <dd class="text-gray-800 mt-0.5" x-text="detail?.manuscript?.submitted_at"></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kata Kunci</dt>
                    <dd class="text-gray-800 mt-0.5" x-text="detail?.manuscript?.keywords || '—'"></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Abstrak</dt>
                    <dd class="text-gray-700 mt-0.5 whitespace-pre-line" x-text="detail?.manuscript?.abstract || '—'"></dd>
                </div>
                <div class="sm:col-span-2" x-show="(detail?.manuscript?.co_authors ?? []).length">
                    <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Penulis Pendamping</dt>
                    <dd class="text-gray-700 mt-0.5">
                        <ul class="list-disc list-inside">
                            <template x-for="c in detail?.manuscript?.co_authors ?? []" :key="c.name + c.email">
                                <li><span x-text="c.name"></span> <span class="text-gray-400" x-show="c.email" x-text="`(${c.email})`"></span></li>
                            </template>
                        </ul>
                    </dd>
                </div>
            </dl>

            {{-- Berkas --}}
            <div class="flex flex-wrap gap-3">
                <a :href="detail?.manuscript?.file_url"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh Naskah (<span x-text="detail?.manuscript?.file_name || 'berkas'"></span>)
                </a>
                <a x-show="detail?.manuscript?.revision_url" :href="detail?.manuscript?.revision_url"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh Revisi (<span x-text="detail?.manuscript?.revision_name || 'berkas'"></span>)
                </a>
            </div>
        </div>

        <hr class="border-gray-100">

        {{-- Timeline --}}
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Progres Naskah</p>
            <ol class="relative">
                <template x-for="(step, i) in detail?.timeline ?? []" :key="step.key">
                    <li class="relative flex gap-3 pb-6 last:pb-0">
                        {{-- garis penghubung --}}
                        <span x-show="i < detail.timeline.length - 1"
                              class="absolute left-[13px] top-7 bottom-0 w-0.5"
                              :class="step.state === 'done' ? 'bg-green-400' : 'bg-gray-200'"></span>
                        {{-- penanda --}}
                        <span class="relative z-10 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                              :class="{
                                  'bg-green-500 text-white': step.state === 'done',
                                  'bg-red-600 text-white ring-4 ring-red-100': step.state === 'current',
                                  'bg-red-600 text-white': step.state === 'rejected',
                                  'bg-gray-200 text-gray-500': step.state === 'upcoming',
                                  'bg-gray-100 text-gray-400 border border-dashed border-gray-300': step.state === 'skipped',
                              }">
                            <template x-if="step.state === 'done'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></template>
                            <template x-if="step.state === 'rejected'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></template>
                            <template x-if="!['done','rejected'].includes(step.state)"><span x-text="i + 1"></span></template>
                        </span>
                        <div class="pt-0.5">
                            <p class="text-sm font-semibold"
                               :class="['upcoming','skipped'].includes(step.state) ? 'text-gray-400' : 'text-gray-800'"
                               x-text="step.state === 'rejected' ? 'Decision — Ditolak' : step.label"></p>
                            <p class="text-xs text-gray-400" x-show="step.date" x-text="step.date"></p>
                            <p class="text-xs text-red-600 font-medium" x-show="step.state === 'current'">Tahap saat ini</p>
                            <p class="text-xs text-gray-400" x-show="step.state === 'skipped'">Tidak diperlukan</p>
                        </div>
                    </li>
                </template>
            </ol>
        </div>

        {{-- Catatan editorial (bila ada) --}}
        <div x-show="detail?.manuscript?.editorial_note" class="rounded-xl border border-orange-200 bg-orange-50/60 p-4">
            <p class="text-xs font-semibold text-orange-800 uppercase tracking-wider mb-1">
                Catatan Editor <span class="font-normal normal-case" x-show="detail?.manuscript?.decided_at" x-text="`· ${detail.manuscript.decided_at}`"></span>
            </p>
            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="detail?.manuscript?.editorial_note"></p>
        </div>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function trackingModal() {
    return editorModal({
        detailUrl: @js(route('author.manuscripts.show', '__ID__')),
        open(id) { this.openModal(this.detailUrl.replace('__ID__', id)); },
    });
}
</script>
@endpush
