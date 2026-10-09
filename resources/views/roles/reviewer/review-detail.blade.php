
@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50">

    {{-- HEADER HALAMAN --}}
    <div class="mb-6">
        <div class="mb-3 flex items-center gap-3">
            <a
                href="{{ route('reviewer.manuscripts.index') }}"
                class="text-2xl font-semibold text-gray-900 hover:text-gray-600">
                ‹
            </a>

            <h1 class="text-2xl font-bold text-gray-900">
                Review Naskah
            </h1>
        </div>

        {{-- JUDUL NASKAH --}}
        <h2 class="ml-8 text-lg font-medium text-gray-900">
            {{ $review->manuscript->title ?? 'Judul naskah tidak tersedia' }}
        </h2>
    </div>

    {{-- KONTEN UTAMA --}}
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">

        {{-- INFORMASI NASKAH --}}
        <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-5">

            <h3 class="mb-6 text-base font-bold text-gray-900">
                Informasi Naskah
            </h3>

            {{-- BIDANG --}}
            <p class="mb-1 text-sm text-gray-700">
                Bidang
            </p>

            <p class="text-sm font-medium text-gray-900">
                {{ $review->manuscript->researchField->name ?? '-' }}
            </p>

            {{-- TANGGAL PENUGASAN --}}
            <div class="mb-8">
                <p class="mb-1 text-sm text-gray-700">
                    Tanggal Penugasan
                </p>

                <p class="text-sm font-medium text-gray-900">
                    {{ $review->created_at?->format('d/m/Y') ?? '-' }}
                </p>
            </div>

            {{-- BATAS REVIEW --}}
            <div class="mb-8">
                <p class="mb-1 text-sm text-gray-700">
                    Batas Review
                </p>

                <p class="text-sm font-medium text-gray-900">
                    {{ $review->due_at?->format('d/m/Y') ?? '-' }}
                </p>
            </div>

            {{-- ABSTRAK --}}
            <div class="mb-6 border-t border-gray-300 pt-5">
                <p class="mb-2 text-sm font-medium text-gray-900">
                    Abstrak
                </p>

                <p class="text-sm leading-6 text-gray-600">
                    {{ $review->manuscript->abstract ?? 'Abstrak belum tersedia.' }}
                </p>
            </div>

            {{-- FILE NASKAH --}}
            <div class="mb-3">
                <div class="inline-flex items-center gap-2 rounded-lg border border-gray-400 bg-gray-200 px-3 py-2 text-sm text-gray-900">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M7 3.75h7l5 5v11.5A1.75 1.75 0 0 1 17.25 22h-10.5A1.75 1.75 0 0 1 5 20.25V5.5A1.75 1.75 0 0 1 6.75 3.75Z" />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14 4v5h5M8 14h8M8 17h8" />
                    </svg>

                    {{ $review->manuscript->file_original_name ?? 'Nama file tidak tersedia' }}
                </div>
            </div>

            {{-- TOMBOL UNDUH --}}
            <div>
                <a
                    href="{{ $review->manuscript->file_path ? \Illuminate\Support\Facades\Storage::url($review->manuscript->file_path) : '#' }}"
                    @if (empty($review->manuscript->file_path))
                        onclick="event.preventDefault(); alert('File PDF masih menggunakan data dummy.')"
                    @endif
                    class="inline-flex items-center justify-center rounded-lg bg-green-300 px-6 py-2 text-sm font-medium text-gray-900 transition-colors hover:bg-green-400">
                    Unduh Naskah
                </a>
            </div>

        </div>

        {{-- FORM REVIEW --}}
        <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-7">

            <h3 class="mb-2 text-base font-bold text-gray-900">
                Form Review
            </h3>

            <p class="mb-6 text-sm text-gray-600">
                Berikan penilaian terhadap naskah dan tuliskan rekomendasi
                berdasarkan hasil pemeriksaan.
            </p>

            <form
                x-data="{
                    aksi: '',
                    relevansi: '',
                    metodologi: '',
                    komentar: '',
                    rekomendasi: '',
                    prosesReview() {
                        if (
                            !this.relevansi ||
                            !this.metodologi ||
                            !this.komentar.trim() ||
                            !this.rekomendasi
                        ) {
                            alert('Mohon lengkapi semua penilaian, komentar, dan rekomendasi.');
                            return;
                        }

                        if (this.aksi === 'simpan') {
                            alert('Simulasi: draf review berhasil disimpan sementara.');
                            return;
                        }

                        if (this.aksi === 'kirim') {
                            if (confirm('Yakin ingin mengirim hasil review ini?')) {
                                alert('Simulasi: review berhasil dikirim.');
                            }
                        }
                    }
                }"
                @submit.prevent="prosesReview()">

                @csrf

                {{-- PENILAIAN NASKAH --}}
                <fieldset class="mb-6">
                    <legend class="mb-3 text-sm font-medium text-gray-900">
                        Penilaian Naskah
                    </legend>

                    {{-- RELEVANSI TOPIK --}}
                    <div class="mb-5">
                        <p class="mb-2 text-sm text-gray-800">
                            Relevansi Topik
                        </p>

                        <div class="space-y-2">
                            @foreach (['Sangat Baik', 'Baik', 'Cukup', 'Kurang'] as $nilai)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800">
                                    <input
                                        type="radio"
                                        name="relevansi_topik"
                                        value="{{ $nilai }}"
                                        x-model="relevansi"
                                        class="h-4 w-4 accent-blue-600">

                                    {{ $nilai }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- METODOLOGI PENELITIAN --}}
                    <div>
                        <p class="mb-2 text-sm text-gray-800">
                            Metodologi Penelitian
                        </p>

                        <div class="space-y-2">
                            @foreach (['Sangat Baik', 'Baik', 'Cukup', 'Kurang'] as $nilai)
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800">
                                    <input
                                        type="radio"
                                        name="metodologi_penelitian"
                                        value="{{ $nilai }}"
                                        x-model="metodologi"
                                        class="h-4 w-4 accent-blue-600">

                                    {{ $nilai }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </fieldset>

                {{-- KOMENTAR REVIEWER --}}
                <div class="mb-5">
                    <label
                        for="komentar"
                        class="mb-2 block text-sm font-medium text-gray-900">
                        Komentar Reviewer
                    </label>

                    <textarea
                        id="komentar"
                        name="komentar"
                        rows="4"
                        x-model="komentar"
                        placeholder="Tulis komentar, kritik, atau masukan untuk penulis..."
                        class="w-full resize-y rounded-lg border border-gray-400 bg-gray-100 px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
                </div>

                {{-- REKOMENDASI --}}
                <fieldset class="mb-6">
                    <legend class="mb-3 text-sm font-medium text-gray-900">
                        Rekomendasi
                    </legend>

                    <div class="space-y-2">
                        @foreach (['Diterima', 'Revisi Minor', 'Revisi Mayor', 'Ditolak'] as $nilai)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800">
                                <input
                                    type="radio"
                                    name="rekomendasi"
                                    value="{{ $nilai }}"
                                    x-model="rekomendasi"
                                    class="h-4 w-4 accent-blue-600">

                                {{ $nilai }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- TOMBOL AKSI --}}
                <div class="flex flex-wrap justify-center gap-3">

                    <button
                        type="button"
                        @click="aksi = 'simpan'; prosesReview()"
                        class="rounded-lg border border-gray-400 bg-gray-200 px-6 py-2 text-sm font-medium text-gray-900 transition-colors hover:bg-gray-300">
                        Simpan Review
                    </button>

                    <button
                        type="button"
                        @click="aksi = 'kirim'; prosesReview()"
                        class="rounded-lg border border-gray-400 bg-gray-200 px-6 py-2 text-sm font-medium text-gray-900 transition-colors hover:bg-gray-300">
                        Kirim Review
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection