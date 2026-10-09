@extends('layouts.guest')

@section('content')
@php
    $submitUrl = auth()->check()
        ? (auth()->user()->role === 'Author' ? route('author.manuscripts.create') : route('dashboard'))
        : route('register');

    $scope = [
        ['Teknologi & Transformasi Digital', 'Sistem informasi, kecerdasan buatan, keamanan siber, dan layanan digital.', 'Teknologi'],
        ['Tata Kelola Pemerintahan', 'Administrasi, kebijakan publik, dan pelayanan publik.', 'Pemerintahan'],
        ['Inovasi Daerah', 'Inovasi pelayanan dan pembangunan berbasis riset di tingkat daerah.', 'Inovasi'],
        ['Kesehatan & Lingkungan', 'Kesehatan masyarakat, lingkungan hidup, dan ketahanan kota.', 'Kesehatan'],
        ['Sosial, Ekonomi & Budaya', 'Dinamika sosial, ekonomi kerakyatan, dan kearifan lokal.', 'Sosial'],
        ['Pendidikan & Pengembangan SDM', 'Inovasi pembelajaran dan peningkatan kapasitas sumber daya manusia.', 'Pendidikan'],
    ];

    $flow = [
        ['Registrasi', 'Buat akun Author lalu lengkapi profil.'],
        ['Submit Naskah', 'Isi metadata, abstrak, kata kunci, dan unggah berkas.'],
        ['Pemeriksaan Editor', 'Editor memeriksa kelengkapan dan kesesuaian naskah.'],
        ['Peer Review', 'Reviewer menilai secara anonim dan memberi rekomendasi.'],
        ['Revisi & Keputusan', 'Perbaiki naskah bila diminta; editor menetapkan keputusan.'],
        ['Terbit', 'Naskah disetujui ditata dan diterbitkan pada edisi jurnal.'],
    ];
@endphp

{{-- HERO --}}
<section class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-red-600">BRIDA Kota Makassar</p>
        <h1 class="mx-auto mt-3 max-w-4xl text-3xl font-extrabold leading-tight text-gray-900 sm:text-5xl">
            Sistem Informasi Manajemen Publikasi Ilmiah
        </h1>
        <p class="mx-auto mt-5 max-w-2xl text-base text-gray-500 sm:text-lg">
            Wadah penerbitan hasil riset dan inovasi daerah Badan Riset dan Inovasi Daerah (BRIDA) Kota Makassar,
            dikelola secara terbuka, terstruktur, dan aman.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ $submitUrl }}" class="w-full sm:w-auto rounded-lg bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">Ajukan Naskah</a>
            <a href="#terbitan" class="w-full sm:w-auto rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Telusuri Artikel</a>
        </div>
    </div>
</section>

{{-- STATISTIK --}}
<section class="-mt-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-public.stat-card label="Artikel Terbit" :value="$stats['articles']" />
            <x-public.stat-card label="Volume" :value="$stats['volumes']" />
            <x-public.stat-card label="Total Unduhan" :value="$stats['downloads']" />
        </div>
    </div>
</section>

{{-- TENTANG --}}
<x-public.section id="tentang" title="Tentang Kami" subtitle="Visi, misi, dan tujuan penerbitan riset serta inovasi daerah.">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-gray-900">Visi</h3>
            <p class="mt-2 text-sm leading-relaxed text-gray-600">Menjadi rujukan publikasi ilmiah yang terpercaya untuk mendukung pembangunan daerah berbasis riset dan inovasi.</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-gray-900">Misi</h3>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-gray-600">
                <li>Mengelola proses penerbitan yang transparan dan akuntabel.</li>
                <li>Menjaga mutu melalui telaah sejawat (peer review).</li>
                <li>Membuka akses hasil riset bagi masyarakat luas.</li>
            </ul>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="font-bold text-gray-900">Tujuan</h3>
            <p class="mt-2 text-sm leading-relaxed text-gray-600">Menyebarluaskan hasil riset dan inovasi kepada pemerintah, akademisi, peneliti, dan masyarakat sebagai dasar kebijakan serta pengembangan keilmuan.</p>
        </div>
    </div>
</x-public.section>

{{-- TERBITAN TERBARU --}}
<x-public.section id="terbitan" tone="white" title="Terbitan Terbaru"
    :subtitle="$current ? $current->label . ($current->title ? ' — ' . $current->title : '') : 'Edisi terbaru yang telah diterbitkan.'">
    @if ($current)
        <div class="mb-8 flex items-center gap-4">
            <x-public.issue-cover :issue="$current" class="h-40 w-30 flex-shrink-0 rounded-lg border border-gray-200 bg-gray-100 object-cover shadow-sm" />
            <div>
                <p class="text-lg font-bold text-gray-900">{{ $current->label }}</p>
                @if ($current->title)<p class="text-sm text-gray-500">{{ $current->title }}</p>@endif
            </div>
        </div>
    @endif

    @if ($current && $current->manuscripts->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($current->manuscripts as $article)
                <x-public.article-card :article="$article->setRelation('issue', $current)" :show-issue="false" />
            @endforeach
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('archives.show', $current) }}" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Lihat Seluruh Edisi Ini</a>
            <a href="{{ route('reader.index') }}" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">Telusuri Semua Artikel</a>
        </div>
    @else
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-10 text-center text-sm text-gray-500">
            Belum ada edisi yang diterbitkan. Terbitan pertama akan tampil di sini.
        </div>
    @endif
</x-public.section>

{{-- ARSIP --}}
<x-public.section id="arsip" title="Arsip Terbitan" subtitle="Edisi-edisi sebelumnya, diurutkan dari yang terbaru.">
    @if ($archive->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($archive as $issue)
                <a href="{{ route('archives.show', $issue) }}" class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <x-public.issue-cover :issue="$issue" />
                    <div>
                        <p class="font-semibold text-gray-900">{{ $issue->label }}</p>
                        @if ($issue->title)<p class="mt-0.5 text-sm text-gray-500">{{ $issue->title }}</p>@endif
                        <p class="mt-3 text-xs font-medium text-red-600">{{ $issue->articles_count }} artikel →</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500">Belum ada edisi sebelumnya.</p>
    @endif
    <a href="{{ route('archives.index') }}" class="mt-6 inline-block text-sm font-semibold text-red-600 hover:text-red-700">Lihat seluruh arsip →</a>
</x-public.section>

{{-- FOKUS & RUANG LINGKUP --}}
<x-public.section id="fokus" tone="white" title="Fokus & Ruang Lingkup" subtitle="Topik riset dan inovasi yang diterima untuk diterbitkan.">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($scope as [$name, $desc, $term])
            <a href="{{ route('reader.index', ['q' => $term]) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-red-300 hover:shadow-md">
                <h3 class="font-semibold text-gray-900 group-hover:text-red-700">{{ $name }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $desc }}</p>
            </a>
        @endforeach
    </div>
</x-public.section>

{{-- PANDUAN PENULIS --}}
<x-public.section id="panduan" title="Panduan Penulis" subtitle="Syarat pengajuan dan alur peer review naskah.">
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="font-bold text-gray-900">Syarat Pengajuan</h3>
                <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-gray-600">
                    <li>Naskah orisinal dan belum dipublikasikan di tempat lain.</li>
                    <li>Berkas PDF atau DOCX, maksimal 10 MB.</li>
                    <li>Memuat judul, abstrak, kata kunci, dan bidang keahlian.</li>
                    <li>Penulis pendamping dicantumkan pada form pengajuan.</li>
                </ul>
            </div>
            <a href="{{ route('guide.template') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Unduh Template Naskah (.docx)
            </a>
        </div>

        <ol class="lg:col-span-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($flow as $i => [$step, $desc])
                <li class="flex gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $step }}</p>
                        <p class="text-sm text-gray-500">{{ $desc }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</x-public.section>
@endsection
