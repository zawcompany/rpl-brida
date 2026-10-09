@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Naskah Ditugaskan
        </h1>

        <p class="mt-2 text-base text-gray-500">
            Berikut Daftar Naskah yang perlu anda telaah
        </p>
    </div>

    {{-- FILTER DAN TABEL --}}
    <div x-data="{
        filterAktif: 'Semua',
        halaman: 1,
        perHalaman: 5,

        jumlahData() {
            return Array.from(this.$el.querySelectorAll('tbody tr'))
                .filter(row =>
                    this.filterAktif === 'Semua' ||
                    row.dataset.status === this.filterAktif
                ).length;
        },

        halamanMaksimal() {
            return Math.max(1, Math.ceil(this.jumlahData() / this.perHalaman));
        },

        nomorHalaman(index) {
            const barisTerfilter = Array.from(
                this.$el.querySelectorAll('tbody tr')
            ).filter(row =>
                this.filterAktif === 'Semua' ||
                row.dataset.status === this.filterAktif
            );

            return Math.floor(barisTerfilter.indexOf(index) / this.perHalaman) + 1;
        }
    }">

        {{-- FILTER --}}
        <div class="mb-8 flex flex-wrap gap-4">

            <button
                type="button"
                @click="filterAktif = 'Semua'; halaman = 1"
                :class="filterAktif === 'Semua'
                    ? 'bg-gray-200 border-gray-200'
                    : 'bg-white border-gray-200 hover:bg-gray-50'"
                class="rounded-lg border px-4 py-4 text-sm font-semibold text-gray-900 transition-colors">
                Semua
            </button>

            <button
                type="button"
                @click="filterAktif = 'Belum Direview'; halaman = 1"
                :class="filterAktif === 'Belum Direview'
                    ? 'bg-gray-200 border-gray-200'
                    : 'bg-white border-gray-200 hover:bg-gray-50'"
                class="rounded-lg border px-4 py-4 text-sm font-semibold text-gray-900 transition-colors">
                Belum Direview
            </button>

            <button
                type="button"
                @click="filterAktif = 'Sedang Direview'; halaman = 1"
                :class="filterAktif === 'Sedang Direview'
                    ? 'bg-gray-200 border-gray-200'
                    : 'bg-white border-gray-200 hover:bg-gray-50'"
                class="rounded-lg border px-4 py-4 text-sm font-semibold text-gray-900 transition-colors">
                Sedang Direview
            </button>

            <button
                type="button"
                @click="filterAktif = 'Selesai Direview'; halaman = 1"
                :class="filterAktif === 'Selesai Direview'
                    ? 'bg-green-200 border-green-200 text-green-900'
                    : 'bg-white border-gray-200 text-gray-900 hover:bg-green-50'"
                class="rounded-lg border px-4 py-4 text-sm font-semibold transition-colors">
                Selesai Direview
            </button>

        </div>

        {{-- TABEL NASKAH --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead>
                        <tr class="bg-gray-100 text-left">

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                No
                            </th>

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                Naskah
                            </th>

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                Bidang Penelitian
                            </th>

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                Batas Review
                            </th>

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                Status
                            </th>

                            <th class="px-5 py-6 text-sm font-semibold text-gray-900">
                                Aksi
                            </th>

                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($manuscripts as $manuscript)

                            <tr
                                x-show="
                                    (filterAktif === 'Semua' ||
                                    filterAktif === @js($manuscript['status']))
                                    &&
                                    Math.floor(
                                        Array.from($el.parentElement.children)
                                            .filter(row =>
                                                filterAktif === 'Semua' ||
                                                row.dataset.status === filterAktif
                                            )
                                            .indexOf($el) / perHalaman
                                    ) + 1 === halaman
                                "
                                data-status="{{ $manuscript['status'] }}"
                                @click="window.location.href = '{{ $manuscript['action'] === 'Selesai' ? route('reviewer.manuscripts-selesai') : route('reviewer.review-detail', ['id' => $manuscript['id']]) }}'" @keydown.enter="window.location.href = '{{ $manuscript['action'] === 'Selesai' ? route('reviewer.manuscripts-selesai') : route('reviewer.review-detail', ['id' => $manuscript['id']]) }}'"
                                tabindex="0"
                                role="link"
                                aria-label="Buka detail naskah {{ $manuscript['title'] }}"
                                class="cursor-pointer border-b border-gray-200 transition-colors hover:bg-gray-100 focus:bg-gray-100 focus:outline-none">

                                {{-- NOMOR --}}
                                <td class="px-5 py-6 text-sm text-gray-900">
                                    {{ $loop->iteration }}
                                </td>

                                {{-- JUDUL NASKAH --}}
                                <td class="max-w-xs px-5 py-6 text-sm text-gray-900">
                                    {{ $manuscript['title'] }}
                                </td>

                                {{-- BIDANG PENELITIAN --}}
                                <td class="px-5 py-6 text-sm text-gray-900">
                                    {{ $manuscript['field'] }}
                                </td>

                                {{-- BATAS REVIEW --}}
                                <td class="px-5 py-6 text-sm text-gray-900">
                                    {{ $manuscript['deadline'] }}
                                </td>

                                {{-- STATUS --}}
                                <td class="px-5 py-6 text-sm">
                                    @if ($manuscript['status'] === 'Belum Direview')
                                        <span class="inline-block rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                            {{ $manuscript['status'] }}
                                        </span>
                                    @elseif ($manuscript['status'] === 'Sedang Direview')
                                        <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                            {{ $manuscript['status'] }}
                                        </span>
                                    @elseif ($manuscript['status'] === 'Selesai Direview')
                                        <span class="inline-block rounded-full bg-amber-200 px-3 py-1 text-xs font-medium text-gray-700">
                                            {{ $manuscript['status'] }}
                                        </span>
                                    @else
                                        <span class="inline-block rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                            {{ $manuscript['status'] }}
                                        </span>
                                    @endif
                                </td>

                        
                                {{-- AKSI --}}
                                <td class="px-5 py-6 text-sm font-medium">
                                    @if ($manuscript['action'] === 'Selesai')
                                        <a
                                            href="{{ route('reviewer.manuscripts-selesai') }}"
                                            @click.stop
                                            class="block text-blue-600 hover:text-blue-800 hover:underline">
                                            Selesai
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('reviewer.review-detail', ['id' => $manuscript['id']]) }}"
                                            @click.stop
                                            class="block text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $manuscript['action'] }}
                                        </a>
                                    @endif
                                </td>


                            </tr>

                        @endforeach
                    </tbody>

                </table>

            </div>

            {{-- FOOTER / PAGINATION --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">

                <p class="text-sm text-gray-500">
                    Halaman
                    <span x-text="halaman"></span>
                    dari
                    <span x-text="halamanMaksimal()"></span>
                </p>

                <div class="flex items-center gap-2">

                    {{-- SEBELUMNYA --}}
                    <button
                        type="button"
                        @click="halaman = Math.max(1, halaman - 1)"
                        :disabled="halaman === 1"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
                        ‹
                    </button>

                    {{-- HALAMAN AKTIF --}}
                    <span
                        x-text="halaman"
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-200 text-sm font-medium text-gray-900">
                    </span>

                    {{-- BERIKUTNYA --}}
                    <button
                        type="button"
                        @click="halaman = Math.min(halaman + 1, halamanMaksimal())"
                        :disabled="halaman >= halamanMaksimal()"
                        class="rounded-md px-3 py-2 text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">
                        ›
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
