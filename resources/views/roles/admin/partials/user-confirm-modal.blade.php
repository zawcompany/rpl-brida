{{-- Modal konfirmasi: toggle status akun ('user-toggle') dan reset password ('user-reset'). --}}
<div x-data="userConfirmModal()"
     x-on:open-editor-modal.window="['user-toggle', 'user-reset'].includes($event.detail.name) && open($event.detail.name, $event.detail.id)">

    <x-editor.modal width="max-w-md" title="Konfirmasi Tindakan">
        <div class="space-y-3 text-sm text-gray-700">
            <p x-show="!result">
                <template x-if="mode === 'toggle'">
                    <span>
                        <span x-text="detail?.user?.is_active ? 'Tangguhkan (suspend)' : 'Aktifkan kembali'"></span>
                        akun <strong x-text="detail?.user?.name"></strong>?
                        <span x-show="detail?.user?.is_active" class="block text-xs text-gray-500 mt-1">Pengguna akan langsung keluar dan tidak dapat masuk sampai diaktifkan kembali.</span>
                    </span>
                </template>
                <template x-if="mode === 'reset'">
                    <span>
                        Reset password akun <strong x-text="detail?.user?.name"></strong>?
                        <span class="block text-xs text-gray-500 mt-1">Password lama tidak berlaku lagi dan diganti password sementara acak.</span>
                    </span>
                </template>
            </p>

            <div x-show="result" class="rounded-lg border border-green-200 bg-green-50 p-4">
                <p class="text-xs font-semibold text-green-800 uppercase tracking-wider mb-1">Password Sementara</p>
                <code class="block text-base font-mono font-bold text-gray-900 select-all break-all" x-text="result"></code>
                <p class="text-xs text-gray-500 mt-2">Hanya ditampilkan sekali. Minta pengguna menggantinya setelah masuk.</p>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" x-show="!result" @click="confirm()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Memproses...' : 'Ya, Lanjutkan'"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function userConfirmModal() {
    return adminModal({
        showUrl: @js(route('admin.users.show', '__ID__')),
        statusUrl: @js(route('admin.users.status', '__ID__')),
        resetUrl: @js(route('admin.users.reset', '__ID__')),
        mode: 'toggle',
        userId: 0,
        result: '',

        open(name, id) {
            this.mode = name === 'user-reset' ? 'reset' : 'toggle';
            this.userId = id;
            this.result = '';
            this.openModal(this.showUrl.replace('__ID__', id));
        },

        async confirm() {
            if (this.mode === 'toggle') {
                return this.run(this.statusUrl.replace('__ID__', this.userId), 'PATCH');
            }
            // reset: tampilkan password sementara dan biarkan modal terbuka
            const res = await this.run(this.resetUrl.replace('__ID__', this.userId), 'POST', {}, false);
            if (res) this.result = res.password;
        },
    });
}
</script>
@endpush
