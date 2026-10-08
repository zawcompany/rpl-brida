<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SIMPIL BRIDA Kota Makassar' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased text-gray-800">

    {{-- HEADER PUBLIK --}}
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200">
        <div class="px-8">

            <div class="h-20 flex items-center justify-between">

                {{-- Logo / Nama SIMPIL --}}
                <a href="{{ route('reader.index') }}"
                   class="flex items-center gap-4">

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            SIMPIL
                        </h1>

                        <p class="text-xs text-gray-500">
                            Sistem Informasi Manajemen Publikasi Ilmiah
                        </p>
                    </div>

                </a>

                {{-- Logo BRIDA --}}
                <a href="{{ route('reader.index') }}">
                    <img
                        src="{{ asset('images/logo-brida.png') }}"
                        alt="BRIDA Kota Makassar"
                        class="h-10 w-auto"
                    >
                </a>

            </div>

        </div>
    </header>

    {{-- ISI HALAMAN --}}
    <main>
        @yield('content')
    </main>

    @stack('scripts')

</body>
</html>