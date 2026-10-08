{{--
    JavaScript modul admin (dimuat sekali per halaman). Melengkapi helper bersama editor-js
    (editorApi, editorModal, dataTable, openEditorModal):
      - adminTable()  : dataTable bersama + filter role
      - adminSend()   : kirim JSON dengan method spoofing (_method) untuk PUT/PATCH
--}}
@include('roles.editor.partials.editor-js')

@once
@push('scripts')
<script>
window.adminTable = function (cfg) {
    const table = window.dataTable(cfg);
    table.role = cfg.role || '';

    Object.defineProperty(table, 'hasFilter', {
        get() { return !!(this.search || this.role); },
        enumerable: true, configurable: true,
    });

    table.reset = function () {
        this.search = '';
        this.role = '';
        this.fetchData(1);
    };

    table.fetchData = async function (page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams({ search: this.search, role: this.role, per_page: this.perPage, page });
            const d = await window.editorApi(`${cfg.url}?${params}`);

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
    };

    return table;
};

// method: 'POST' | 'PUT' | 'PATCH' (selain POST dikirim sebagai POST + _method, setara method spoofing di form Blade)
window.adminSend = function (url, method, body = {}) {
    return window.editorApi(url, {
        method: 'POST',
        body: method === 'POST' ? body : Object.assign({ _method: method }, body),
    });
};

// Modal admin = editorModal + run(): kirim aksi, segarkan tabel, tutup otomatis (opsional).
window.adminModal = function (extra = {}) {
    return window.editorModal(Object.assign({
        async run(url, method, body = {}, autoClose = true) {
            this.errorMsg = '';
            this.successMsg = '';
            this.submitting = true;
            try {
                const res = await window.adminSend(url, method, body);
                this.successMsg = res.message;
                window.dispatchEvent(new CustomEvent('table-refresh'));
                if (autoClose) setTimeout(() => this.close(), 1000);
                return res;
            } catch (e) {
                this.errorMsg = e.message;
                return null;
            } finally {
                this.submitting = false;
            }
        },
    }, extra));
};
</script>
@endpush
@endonce
