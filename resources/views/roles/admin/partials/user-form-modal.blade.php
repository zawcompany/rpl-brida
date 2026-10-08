{{-- Modal Tambah/Edit Pengguna. Dibuka dengan openEditorModal('user-form', id); id 0 = tambah baru. --}}
<div x-data="userFormModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'user-form' && open($event.detail.id)">

    <x-editor.modal width="max-w-2xl"
        title="Data Pengguna" subtitle="Lengkapi data akun. Password disimpan terenkripsi (Bcrypt).">
        <x-slot:footer>
            <button type="button" @click="save()" :disabled="submitting || !!successMsg"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Menyimpan...' : 'Simpan'"></span>
            </button>
        </x-slot:footer>

        <p class="text-sm font-semibold text-red-700" x-text="userId ? 'Edit pengguna' : 'Tambah pengguna baru'"></p>

        <form @submit.prevent="save()" class="grid grid-cols-1 sm:grid-cols-2 gap-4" autocomplete="off">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-name">Nama Lengkap <span class="text-red-500">*</span></label>
                <input id="u-name" type="text" x-model="form.name" maxlength="255" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-email">Email <span class="text-red-500">*</span></label>
                <input id="u-email" type="email" x-model="form.email" maxlength="255" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-password">
                    Password <span class="text-red-500" x-show="!userId">*</span>
                    <span class="font-normal text-gray-400" x-show="userId">(kosongkan bila tidak diganti)</span>
                </label>
                <input id="u-password" type="password" x-model="form.password" minlength="8" autocomplete="new-password"
                       placeholder="Minimal 8 karakter" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-role">Role <span class="text-red-500">*</span></label>
                <select id="u-role" x-model="form.role" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    <option value="">— Pilih Role —</option>
                    @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-phone">Nomor Telepon</label>
                <input id="u-phone" type="text" x-model="form.phone" maxlength="30" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="u-inst">Instansi / Afiliasi</label>
                <input id="u-inst" type="text" x-model="form.institution" maxlength="255" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>

            {{-- Switch status akun --}}
            <div class="sm:col-span-2 flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">Status Akun</p>
                    <p class="text-xs text-gray-500" x-text="form.is_active ? 'Aktif — pengguna dapat masuk.' : 'Suspend — pengguna tidak dapat masuk.'"></p>
                </div>
                <button type="button" role="switch" :aria-checked="form.is_active" @click="form.is_active = !form.is_active"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        :class="form.is_active ? 'bg-red-600' : 'bg-gray-300'">
                    <span class="inline-block h-5 w-5 mt-0.5 rounded-full bg-white shadow transition-transform"
                          :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"></span>
                </button>
            </div>
        </form>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function userFormModal() {
    const blank = () => ({ name: '', email: '', password: '', role: '', institution: '', phone: '', is_active: true });

    return adminModal({
        showUrl: @js(route('admin.users.show', '__ID__')),
        updateUrl: @js(route('admin.users.update', '__ID__')),
        storeUrl: @js(route('admin.users.store')),
        userId: 0,
        form: blank(),

        async open(id) {
            this.userId = id;
            this.form = blank();
            this.errorMsg = '';
            this.successMsg = '';

            if (!id) { this.detail = {}; this.isOpen = true; return; }

            await this.openModal(this.showUrl.replace('__ID__', id));
            if (this.detail?.user) this.form = Object.assign(blank(), this.detail.user, { password: '' });
        },

        save() {
            if (!this.form.name.trim() || !this.form.email.trim() || !this.form.role) {
                this.errorMsg = 'Nama, email, dan role wajib diisi.'; return;
            }
            if (!this.userId && this.form.password.length < 8) {
                this.errorMsg = 'Password minimal 8 karakter.'; return;
            }
            return this.userId
                ? this.run(this.updateUrl.replace('__ID__', this.userId), 'PUT', this.form)
                : this.run(this.storeUrl, 'POST', this.form);
        },
    });
}
</script>
@endpush
