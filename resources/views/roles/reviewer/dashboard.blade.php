@extends('layouts.app')

@section('content')

<div class="min-h-screen bg-gray-50">

    {{-- =========================
         WELCOME REVIEWER
    ========================== --}}
    <div class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-col items-center justify-between p-6 md:flex-row md:p-8">

            {{-- Sisi Kiri: Teks --}}
            <div class="md:w-2/3">

                <p class="mb-1 font-medium text-gray-500">
                    Selamat datang,
                </p>

                <h1 class="mb-4 text-3xl font-bold text-gray-900">
                    Reviewer
                </h1>

                <p class="max-w-4xl leading-relaxed text-gray-600">
                    Silakan lakukan penelaahan terhadap naskah yang ditugaskan secara objektif dan
                    teliti. Berikan penilaian, komentar, serta rekomendasi yang konstruktif untuk
                    membantu Editor dalam menentukan kelayakan naskah
                </p>

            </div>

            {{-- Sisi Kanan: Ilustrasi --}}
            <div class="mt-6 flex justify-end md:mt-0 md:w-1/3">

                <img
                    src="{{ asset('images/reviewer.png') }}"
                    alt="Ilustrasi Reviewer"
                    class="h-auto w-48 object-contain drop-shadow-md"
                    onerror="this.onerror=null; this.src='https://placehold.co/400x300/f3f4f6/4b5563?text=Reviewer+Illustration';"
                >

            </div>

        </div>

    </div>


    {{-- =========================
        STATISTIK REVIEWER
    ========================== --}}
    <div class="flex w-full flex-nowrap gap-5">

        {{-- Naskah Ditugaskan --}}
        <div class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col items-center text-center">

            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6 text-gray-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-5.25Z"
                    />
                </svg>

            </div>

            <p class="text-base font-medium text-gray-900">
                Naskah Ditugaskan
            </p>

            <p class="mt-3 text-4xl font-bold text-gray-900">
                10
            </p>

        </div>


        {{-- Belum Direview --}}
        <div class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col items-center text-center">

            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6 text-gray-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6l4 2"
                    />

                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    />
                </svg>

            </div>

            <p class="text-base font-medium text-gray-900">
                Belum Direview
            </p>

            <p class="mt-3 text-4xl font-bold text-gray-900">
                10
            </p>

        </div>


        {{-- Sedang Direview --}}
        <div class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col items-center text-center">

            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6 text-gray-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.862 4.487 18.5 2.85a2.121 2.121 0 1 1 3 3L8.25 19.1 4 20l.9-4.25L16.862 4.487Z"
                    />
                </svg>

            </div>

            <p class="text-base font-medium text-gray-900">
                Sedang Direview
            </p>

            <p class="mt-3 text-4xl font-bold text-gray-900">
                10
            </p>

        </div>


        {{-- Selesai Direview --}}
        <div class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white p-6 shadow-sm flex flex-col items-center text-center">

            <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="h-6 w-6 text-gray-500"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m4.5 12.75 6 6 9-13.5"
                    />
                </svg>

            </div>

            <p class="text-base font-medium text-gray-900">
                Selesai Direview
            </p>

            <p class="mt-3 text-4xl font-bold text-gray-900">
                10
            </p>

        </div>

    </div>

</div>

@endsection