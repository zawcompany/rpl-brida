@extends('layouts.guest')

@section('content')

<div class="min-h-screen bg-gray-50">

    <main class="px-8 pt-12 pb-8">

        {{-- Intro --}}
        <div class="mb-7">

            <h2 class="text-2xl font-bold text-gray-900">
                SIMPIL
            </h2>

            <p class="mt-4 text-lg text-gray-700">
                Sistem Informasi Manajemen Publikasi Ilmiah
            </p>

        </div>


        {{-- Deskripsi --}}
        <p class="mb-6 text-xl text-gray-500">
            Temukan berbagai hasil riset dan publikasi ilmiah
        </p>


        {{-- Search --}}
        <form method="GET" action="{{ route('reader.index') }}">

            <div class="flex items-center rounded-full border border-gray-300 bg-white px-6 py-4 shadow-sm">

                {{-- Search Icon --}}
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="mr-4 h-5 w-5 text-gray-400"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.04 6.04a7.5 7.5 0 0 0 10.61 10.61Z"
                    />
                </svg>


                {{-- Input Search --}}
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari artikel atau kata kunci artikel"
                    class="w-full bg-transparent text-gray-700 outline-none focus:outline-none focus:ring-0"
                >


                {{-- Filter Icon --}}
                <button
                    type="submit"
                    class="ml-3 text-gray-500 transition hover:text-gray-800"
                    title="Filter"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="h-6 w-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 6h18M6 12h12m-8 6h4"
                        />
                    </svg>

                </button>

            </div>

        </form>


        {{-- Filter kategori --}}
        <div class="mt-3 flex flex-wrap gap-3">

            <a
                href="{{ route('reader.index') }}"
                class="rounded-full border border-gray-300 bg-white px-5 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
            >
                Semua
            </a>

            <a
                href="#"
                class="rounded-full border border-gray-300 bg-white px-5 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
            >
                Teknologi
            </a>

            <a
                href="#"
                class="rounded-full border border-gray-300 bg-white px-5 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
            >
                Kesehatan
            </a>

            <a
                href="#"
                class="rounded-full border border-gray-300 bg-white px-5 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
            >
                Lingkungan
            </a>

            <a
                href="#"
                class="rounded-full border border-gray-300 bg-white px-5 py-2 text-sm text-gray-700 transition hover:bg-gray-100"
            >
                Sosial
            </a>

        </div>


        {{-- Trending --}}
        <h2 class="mt-6 mb-2 text-2xl font-semibold text-gray-900">
            Trending Artikel
        </h2>


        {{-- Artikel --}}
        <div class="space-y-3">

            @foreach ($articles as $id => $article)

                <a
                    href="{{ route('reader.article', ['id' => $id]) }}"
                    class="block rounded-lg bg-white p-5 shadow-sm transition hover:shadow-md"
                >

                    <h3 class="text-lg font-medium text-gray-900">
                        {{ $article['title'] }}
                    </h3>

                    <p class="mt-2 text-base text-gray-700">
                        {{ $article['description'] }}
                    </p>

                    <div class="mt-4 text-sm text-gray-400">

                        <span>Tahun {{ $article['year'] }}</span>

                        <span class="mx-2">·</span>

                        <span>{{ $article['author'] }}</span>

                        <span class="mx-2">·</span>

                        <span>{{ $article['field'] }}</span>

                        <span class="mx-2">·</span>

                        <span>Kata Kunci: {{ $article['keywords'] }}</span>

                    </div>

                </a>

            @endforeach

        </div>

    </main>

</div>

@endsection