{{--
    JavaScript bersama modul editor (dimuat sekali per halaman via @once):
      - editorApi()      : fetch JSON/FormData + CSRF + error validasi + sesi berakhir (401/419 -> /login)
      - editorModal()    : state dasar modal (buka, muat detail, submit, tutup)
      - dataTable()      : tabel interaktif (live search, filter, pagination AJAX)
      - openEditorModal(): membuka modal dari tombol di baris tabel
--}}
@once
@push('scripts')
<script>
window.editorApi = async function (url, { method = 'GET', body = null } = {}) {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
    };
    // FormData (unggah berkas) dikirim apa adanya agar browser mengatur boundary multipart.
    const isForm = body instanceof FormData;
    if (body && !isForm) headers['Content-Type'] = 'application/json';

    const res = await fetch(url, { method, headers, body: body ? (isForm ? body : JSON.stringify(body)) : null });
    let data = {};
    try { data = await res.json(); } catch (e) { /* respons non-JSON */ }

    if (!res.ok) {
        // Sesi/token CSRF kedaluwarsa (mis. tab lama dibuka kembali): alihkan ke login, bukan error mentah.
        if (res.status === 401 || res.status === 419) {
            window.location.assign(data.redirect || '/login');
            throw new Error(data.message || 'Sesi Anda telah berakhir. Mengalihkan ke halaman masuk...');
        }
        if (res.status === 403) {
            throw new Error('Anda tidak memiliki akses untuk tindakan ini. Bila Anda berganti akun di tab lain, muat ulang halaman.');
        }
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(firstError || data.message || 'Terjadi kesalahan. Coba lagi.');
    }
    return data;
};

window.openEditorModal = function (name, id) {
    window.dispatchEvent(new CustomEvent('open-editor-modal', { detail: { name, id } }));
};
// Kompatibilitas dengan tombol lama di dashboard
window.openManuscriptModal = function (id) { window.openEditorModal('manuscript', id); };

window.editorModal = function (extra = {}) {
    return Object.assign({
        isOpen: false,
        loading: false,
        submitting: false,
        errorMsg: '',
        successMsg: '',
        detail: null,

        async openModal(url) {
            this.errorMsg = '';
            this.successMsg = '';
            this.detail = null;
            this.isOpen = true;
            this.loading = true;
            try {
                this.detail = await window.editorApi(url);
                if (this.onLoaded) this.onLoaded(this.detail);
            } catch (e) {
                this.errorMsg = e.message;
            } finally {
                this.loading = false;
            }
        },

        close() { this.isOpen = false; },

        // POST JSON; bila sukses tampilkan pesan, segarkan tabel, lalu tutup modal.
        async submit(url, body = {}) {
            this.errorMsg = '';
            this.successMsg = '';
            this.submitting = true;
            try {
                const res = await window.editorApi(url, { method: 'POST', body });
                this.successMsg = res.message;
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('table-refresh'));
                    this.close();
                }, 1200);
                return res;
            } catch (e) {
                this.errorMsg = e.message;
                return null;
            } finally {
                this.submitting = false;
            }
        },
    }, extra);
};

window.dataTable = function (cfg) {
    return {
        search: '',
        date: '',
        field: '',
        perPage: 10,
        page: 1,
        loading: false,
        total: cfg.total,

        init() {
            window.addEventListener('table-refresh', () => this.fetchData(this.page));
        },

        get hasFilter() { return !!(this.search || this.date || this.field); },

        reset() {
            this.search = '';
            this.date = '';
            this.field = '';
            this.fetchData(1);
        },

        async fetchData(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    search: this.search, date: this.date, field: this.field,
                    per_page: this.perPage, page,
                });
                const d = await window.editorApi(`${cfg.url}?${params}`);

                // Halaman terakhir kosong setelah aksi (mis. baris terakhir diproses): mundur satu halaman.
                if (page > d.last_page && d.last_page >= 1) return this.fetchData(d.last_page);

                this.page = page;
                this.$refs.body.innerHTML = d.html;
                this.$refs.links.innerHTML = d.links;
                this.total = d.total;
            } catch (e) {
                // tabel tetap menampilkan data lama
            } finally {
                this.loading = false;
            }
        },

        // Delegasi klik pada link pagination yang dirender server.
        goTo(e) {
            const a = e.target.closest('a');
            if (!a) return;
            e.preventDefault();
            this.fetchData(new URL(a.href).searchParams.get('page') || 1);
        },
    };
};
</script>
@endpush
@endonce
