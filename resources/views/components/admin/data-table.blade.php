{{--
    DataTable modul admin (Control Bar + tabel + pagination), Alpine + AJAX.
    Props: url, headers, rows, rowsView, roles ([nilai => label] filter role), role (nilai awal), placeholder, unit
--}}
@props([
    'url',
    'headers',
    'rows',
    'rowsView',
    'roles' => [],
    'role' => '',
    'placeholder' => 'Cari...',
    'unit' => 'data',
])

@include('roles.admin.partials.admin-js')

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
     x-data="adminTable({{ Js::from(['url' => $url, 'total' => $rows->total(), 'role' => $role]) }})">

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
        <div class="flex items-center gap-2 text-sm text-gray-600">
            <span>Tampilkan</span>
            <select x-model.number="perPage" @change="fetchData(1)" aria-label="Jumlah data per halaman"
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" x-model="search" @input.debounce.350ms="fetchData(1)" placeholder="{{ $placeholder }}"
                       class="block w-full sm:w-64 pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white">
            </div>

            @if (count($roles))
                <select x-model="role" @change="fetchData(1)" aria-label="Filter role"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white text-gray-600">
                    <option value="">Semua Role</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            @endif

            <button type="button" x-show="hasFilter" x-cloak @click="reset()"
                    class="px-3 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Reset</button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach ($headers as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody x-ref="body" class="bg-white divide-y divide-gray-50" :class="loading && 'opacity-50'">
                @include($rowsView, ['rows' => $rows])
            </tbody>
        </table>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between px-5 py-4 border-t border-gray-100 gap-3">
        <p class="text-xs text-gray-500">Total: <span class="font-semibold text-gray-700" x-text="total"></span> {{ $unit }}</p>
        <div x-ref="links" @click="goTo($event)" class="text-sm">{{ $rows->links() }}</div>
    </div>
</div>
