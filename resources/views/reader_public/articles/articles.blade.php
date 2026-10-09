@extends('layouts.guest')

@section('content')

<div class="min-h-screen bg-gray-50">

    <main class="px-8 pt-8 pb-8">

        {{-- Header Artikel --}}
        <div class="flex items-start justify-between">

            {{-- Judul + Penulis --}}
            <div class="flex items-start gap-4">

                {{-- Tombol Kembali --}}
                <a
                    href="{{ route('reader.index') }}"
                    class="mt-1 text-2xl leading-none text-gray-900 hover:text-gray-500"
                >
                    ←
                </a>

                <div>

                    {{-- Judul --}}
                    <h1 class="text-2xl font-bold leading-tight text-gray-900">
                        {{ $article['title'] }}
                    </h1>

                </div>

            </div>


            {{-- Tombol Download --}}
            <a
                href="#"
                class="rounded-lg bg-green-300 px-6 py-3 text-sm font-medium text-gray-900 transition hover:bg-green-400"
            >
                <span class="font-semibold">
                    Unduh Artikel
                <span>
            </a>

        </div>


        {{-- Informasi Artikel --}}
        <div class="mt-5">
            {{-- Penulis --}}
            <p class="mt-4 text-sm text-gray-900">
                    <span class="font-semibold">
                        Penulis:
                    </span>
                        {{ $article['author'] }}
            </p>

            {{-- Bidang --}}
            <p class="mt-2 text-sm text-gray-900">
                {{ $article['field'] }}
            </p>

            {{-- Tahun --}}
            <p class="mt-2 text-sm text-gray-900">
                Dipublikasikan: {{ $article['year'] }}
            </p>

            {{-- Kata Kunci --}}
            <p class="mt-2 text-sm text-gray-900">
                Kata Kunci: {{ $article['keywords'] }}
            </p>

            {{-- Abstrak --}}
            <div class="mt-4">

                <p class="text-sm text-gray-900">
                    <span class="font-semibold">    
                        Abstrak
                    <span>
                </p>

                <p class="mt-0.5 max-w-5xl text-sm leading-6 text-gray-900">
                    {{ $article['abstract'] }}
                </p>

            </div>

        </div>


        {{-- AREA PDF --}}
        <div
            class="mt-4 rounded-lg bg-white px-12 py-12 shadow-sm"
            style="height: 745px;"
        >

            <div
                class="mx-auto flex max-w-2xl items-center justify-center rounded-[35px] bg-gray-200"
                style="height: 640px;"
            >

                <p class="text-base font-medium text-gray-900">
                    ARTIKEL DALAM BENTUK PDF
                </p>

            </div>

        </div>

    </main>

</div>

@endsection