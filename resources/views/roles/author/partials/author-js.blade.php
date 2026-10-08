{{--
    JavaScript modul author (dimuat sekali per halaman via @once). Melengkapi helper bersama editor-js
    (editorApi, editorModal, dataTable, openEditorModal) tanpa mengubahnya:
      - authorTable()  : dataTable bersama + filter status
      - authorUpload() : POST multipart (unggah berkas) dengan penanganan error validasi
--}}
@include('roles.editor.partials.editor-js')

@once
@push('scripts')
<script>
window.authorTable = function (cfg) {
    const table = window.dataTable(cfg);
    table.status = cfg.status || '';

    Object.defineProperty(table, 'hasFilter', {
        get() { return !!(this.search || this.status); },
        enumerable: true, configurable: true,
    });

    table.reset = function () {
        this.search = '';
        this.status = '';
        this.fetchData(1);
    };

    table.fetchData = async function (page = 1) {
        this.loading = true;
        try {
            const params = new URLSearchParams({
                search: this.search, status: this.status, per_page: this.perPage, page,
            });
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

window.authorUpload = async function (url, formData) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: formData,
    });
    let data = {};
    try { data = await res.json(); } catch (e) { /* respons non-JSON (mis. 413) */ }

    if (!res.ok) {
        const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new Error(firstError || data.message || 'Gagal mengunggah. Periksa ukuran berkas (maks. 10 MB).');
    }
    return data;
};
</script>
@endpush
@endonce
