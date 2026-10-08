{{--
    Reusable DataTable (Control Bar + tabel + pagination), berbasis Alpine + AJAX.

    Props:
      url        : endpoint listing (mengembalikan JSON {html, links, total, last_page} bila AJAX)
      headers    : array judul kolom
      rows       : paginator awal (render server-side)
      rowsView   : partial baris (menerima $rows)
      filter     : 'date' (filter tanggal) | 'field' (filter bidang keahlian)
      fields     : koleksi bidang keahlian (untuk filter 'field')
      placeholder: placeholder kotak cari
      unit       : satuan data pada footer ("naskah", "reviewer")
--}}
@props([
    'url',
    'headers',
    'rows',
    'rowsView',
    'filter' => 'date',
    'fields' => [],
    'placeholder' => 'Cari...',
    'unit' => 'data',
])

@include('roles.editor.partials.editor-js')

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
     x-data="dataTable({{ Js::from(['url' => $url, 'total' => $rows->total()]) }})">

    {{-- CONTROL BAR --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <span>Tampilkan</span>
            <select x-model.number="perPage" @change="fetchData(1)"
                    class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
            <span>data</span>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" x-model="search" @input.debounce.350ms="fetchData(1)"
                       placeholder="{{ $placeholder }}"
                       class="block w-full sm:w-64 pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
            </div>

            @if ($filter === 'date')
                <input type="date" x-model="date" @change="fetchData(1)" aria-label="Filter tanggal"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white text-gray-600">
            @elseif ($filter === 'field')
                <select x-model="field" @change="fetchData(1)" aria-label="Filter bidang keahlian"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white text-gray-600">
                    <option value="">Semua Bidang Keahlian</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                    @endforeach
                </select>
            @endif

            <button type="button" x-show="hasFilter" x-cloak @click="reset()"
                    class="px-3 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Reset
            </button>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach ($headers as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody x-ref="body" class="bg-white divide-y divide-gray-50" :class="loading && 'opacity-50'">
                @include($rowsView, ['rows' => $rows])
            </tbody>
        </table>
    </div>

    {{-- FOOTER: total + pagination --}}
    <div class="flex flex-col sm:flex-row items-center justify-between px-5 py-4 border-t border-gray-100 gap-3">
        <p class="text-xs text-gray-500">
            Total: <span class="font-semibold text-gray-700" x-text="total"></span> {{ $unit }}
        </p>
        <div x-ref="links" @click="goTo($event)" class="text-sm">{{ $rows->links() }}</div>
    </div>
</div>
