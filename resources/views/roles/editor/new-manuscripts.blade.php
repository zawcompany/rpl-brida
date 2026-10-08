@extends('layouts.app')

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Naskah Baru</h1>
    <p class="text-sm text-gray-500 mt-1">
        Daftar naskah yang masuk dan menunggu pemeriksaan administrasi awal.
    </p>
</div>

{{-- ============================================================ --}}
{{-- DATA TABLE CARD --}}
{{-- ============================================================ --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
     x-data="manuscriptTable()" x-init="init()">

    {{-- CONTROL BAR --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">

        {{-- Entries selector --}}
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <span>Tampilkan</span>
            <select x-model="perPage" @change="fetchData()"
                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-colors">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>data</span>
        </div>

        {{-- Search & Date Filter --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
            {{-- Search --}}
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" x-model="search"
                       @input.debounce.350ms="fetchData()"
                       placeholder="Cari judul atau penulis..."
                       class="block w-full sm:w-64 pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-colors">
            </div>

            {{-- Date Filter --}}
            <input type="date" x-model="dateFilter"
                   @change="fetchData()"
                   class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white transition-colors text-gray-600">

            {{-- Clear Filter --}}
            <button x-show="search || dateFilter"
                    @click="clearFilters()"
                    class="px-3 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Reset
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Naskah</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penulis</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Masuk</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody id="manuscriptTableBody" class="bg-white divide-y divide-gray-50">
                {{-- Initial server-rendered rows --}}
                @include('roles.editor.partials.manuscript-table-rows', ['manuscripts' => $manuscripts])
            </tbody>
        </table>
    </div>

    {{-- LOADING OVERLAY --}}
    <div x-show="loading"
         class="flex items-center justify-center py-8 border-t border-gray-100">
        <svg class="animate-spin w-6 h-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span class="ml-2 text-sm text-gray-400">Memuat...</span>
    </div>

    {{-- PAGINATION --}}
    <div class="flex flex-col sm:flex-row items-center justify-between px-5 py-4 border-t border-gray-100 gap-3">
        <p class="text-xs text-gray-500">
            Total: <span class="font-semibold text-gray-700" x-text="total"></span> naskah
        </p>
        <div id="paginationLinks" class="flex items-center gap-1 text-sm">
            {{-- Initial server-rendered pagination --}}
            {{ $manuscripts->links() }}
        </div>
    </div>
</div>

{{-- Modal reusable --}}
@include('roles.editor.partials.manuscript-modal')

@endsection

@push('scripts')
<script>
// Alpine.js component untuk tabel interaktif
function manuscriptTable() {
    return {
        search: '{{ $filters["search"] ?? "" }}',
        dateFilter: '{{ $filters["date"] ?? "" }}',
        perPage: {{ $filters["per_page"] ?? 10 }},
        loading: false,
        total: {{ $manuscripts->total() }},

        init() {
            // Refresh tabel setelah modal submit berhasil
            window.addEventListener('manuscript-processed', () => this.fetchData());
        },

        clearFilters() {
            this.search = '';
            this.dateFilter = '';
            this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    search:   this.search,
                    date:     this.dateFilter,
                    per_page: this.perPage,
                }).toString();

                const res = await fetch(`{{ route('editor.manuscripts.new') }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                if (!res.ok) throw new Error();
                const data = await res.json();

                document.getElementById('manuscriptTableBody').innerHTML = data.html;
                document.getElementById('paginationLinks').innerHTML     = data.links;
                this.total = data.total;

                // Re-bind pagination links ke AJAX
                this.bindPaginationLinks();
            } catch {
                // silent fail — tabel tetap menampilkan data lama
            } finally {
                this.loading = false;
            }
        },

        bindPaginationLinks() {
            document.querySelectorAll('#paginationLinks a').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = new URL(link.href);
                    // Ambil page lalu fetch dengan filter aktif
                    const page = url.searchParams.get('page') || 1;
                    const params = new URLSearchParams({
                        search:   this.search,
                        date:     this.dateFilter,
                        per_page: this.perPage,
                        page:     page,
                    }).toString();
                    fetch(`{{ route('editor.manuscripts.new') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('manuscriptTableBody').innerHTML = data.html;
                        document.getElementById('paginationLinks').innerHTML     = data.links;
                        this.total = data.total;
                        this.bindPaginationLinks();
                    });
                });
            });
        },
    };
}

// Fungsi global — dipanggil dari tombol di dalam baris tabel
window.openManuscriptModal = function(id) {
    window.dispatchEvent(new CustomEvent('open-manuscript-modal', { detail: { id } }));
};
</script>
@endpush
