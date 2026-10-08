{{-- Modal Ubah Role (RBAC). Dibuka dengan openEditorModal('user-role', id). --}}
<div x-data="roleModal()"
     x-on:open-editor-modal.window="$event.detail.name === 'user-role' && open($event.detail.id)">

    <x-editor.modal width="max-w-md" title="Ubah Role Pengguna" subtitle="Perubahan berlaku pada login berikutnya">
        <div class="space-y-4">
            <div>
                <p class="text-sm font-semibold text-gray-900" x-text="detail?.user?.name"></p>
                <p class="text-xs text-gray-500" x-text="detail?.user?.email"></p>
                <p class="text-xs text-gray-500 mt-1">Role saat ini: <strong x-text="detail?.user?.role_label"></strong></p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="r-role">Role Baru <span class="text-red-500">*</span></label>
                <select id="r-role" x-model="role" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                    @foreach (\App\Models\User::ROLE_LABELS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" @click="save()" :disabled="submitting || !!successMsg || role === detail?.user?.role"
                    class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-text="submitting ? 'Menyimpan...' : 'Perbarui Role'"></span>
            </button>
        </x-slot:footer>
    </x-editor.modal>
</div>

@push('scripts')
<script>
function roleModal() {
    return adminModal({
        showUrl: @js(route('admin.users.show', '__ID__')),
        roleUrl: @js(route('admin.users.role', '__ID__')),
        userId: 0,
        role: '',

        async open(id) {
            this.userId = id;
            await this.openModal(this.showUrl.replace('__ID__', id));
            this.role = this.detail?.user?.role ?? '';
        },

        save() { return this.run(this.roleUrl.replace('__ID__', this.userId), 'PATCH', { role: this.role }); },
    });
}
</script>
@endpush
