@extends(\App\Support\Nav::layout(), ['title' => $pageTitle . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $pageTitle }}</h1>
    <p class="mt-1 text-xs text-gray-500">Versi {{ $version }} · SIMPIL — Badan Riset dan Inovasi Daerah (BRIDA) Kota Makassar</p>

    <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-xs text-yellow-800">
        Dokumen ini disusun sebagai acuan operasional sistem dan perlu ditinjau oleh unit hukum BRIDA sebelum dinyatakan final.
    </div>

    <div class="mt-6 space-y-6 text-sm leading-relaxed text-gray-700 [&_h2]:text-lg [&_h2]:font-bold [&_h2]:text-gray-900 [&_h2]:mb-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1">
        @yield('legal')
    </div>

    <nav class="mt-10 flex flex-wrap gap-4 border-t border-gray-200 pt-4 text-sm">
        <a href="{{ route('legal.privacy') }}" class="text-red-600 hover:underline">Kebijakan Privasi</a>
        <a href="{{ route('legal.terms') }}" class="text-red-600 hover:underline">Syarat Penggunaan</a>
        <a href="{{ route('legal.ethics') }}" class="text-red-600 hover:underline">Etika Publikasi</a>
    </nav>
</div>
@endsection
