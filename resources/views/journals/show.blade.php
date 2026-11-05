@extends('layouts.guest', ['title' => $journal->name . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-6">
    <a href="{{ route('journals.index') }}" class="text-sm text-gray-500 hover:text-red-600">← Semua jurnal</a>

    <div class="mt-3 mb-8 max-w-3xl">
        @if ($journal->abbreviation)
            <p class="text-xs font-semibold uppercase tracking-wider text-red-600">{{ $journal->abbreviation }}</p>
        @endif
        <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $journal->name }}</h2>
        @if ($journal->description)
            <p class="mt-2 text-gray-600">{{ $journal->description }}</p>
        @endif
        @unless ($journal->is_active)
            <p class="mt-2 inline-block rounded bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">Jurnal nonaktif</p>
        @endunless
    </div>

    @if (session('success'))
        <div role="status" class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Kelompok & template --}}
        <section class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <h3 class="font-bold text-gray-900">Template Naskah</h3>

            @if ($journal->hasTemplate())
                <p class="text-sm text-gray-500">
                    {{ $journal->template_original_name ?: 'Template bawaan jurnal' }}
                    @if ($journal->template_version > 0)<span class="text-gray-400">· versi {{ $journal->template_version }}</span>@endif
                </p>
            @else
                <p class="text-sm text-gray-500">Jurnal ini belum mengunggah template.</p>
            @endif

            @guest
                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    Template hanya untuk anggota jurnal. <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:underline">Masuk</a>
                    atau <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:underline">daftar</a> sebagai penulis, lalu bergabung ke jurnal ini.
                </div>
            @else
                @if ($isMember || auth()->user()->isAdmin())
                    @if ($journal->hasTemplate())
                        <a href="{{ route('journals.template', $journal) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Unduh Template
                        </a>
                    @endif
                @elseif ($canJoin && $journal->is_active)
                    <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                        Bergabung ke kelompok jurnal ini untuk mengunduh template dan mengirim naskah.
                    </div>
                    <form method="POST" action="{{ route('journals.join', $journal) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            Gabung sebagai Penulis
                        </button>
                    </form>
                @else
                    <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                        Template hanya untuk anggota jurnal. Akun {{ auth()->user()->role_label }} tidak bergabung sendiri; hubungi admin atau editor jurnal.
                    </div>
                @endif

                @if ($isAuthor)
                    <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('author.manuscripts.create') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kirim Naskah</a>
                        <form method="POST" action="{{ route('journals.leave', $journal) }}" onsubmit="return confirm('Keluar dari kelompok jurnal ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-500 hover:text-red-600">Keluar dari kelompok</button>
                        </form>
                    </div>
                @endif

                @if ($canManage)
                    <div class="border-t border-gray-100 pt-4">
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.journals.show', $journal) : route('editor.journals.index', ['jurnal' => $journal->id]) }}"
                           class="text-sm font-semibold text-red-600 hover:underline">Kelola jurnal ini →</a>
                    </div>
                @endif
            @endguest
        </section>

        {{-- Edisi terbit --}}
        <section class="lg:col-span-2">
            <h3 class="mb-3 font-bold text-gray-900">Edisi Terbit</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @forelse ($issues as $issue)
                    <a href="{{ route('archives.show', $issue) }}" class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <x-public.issue-cover :issue="$issue" />
                        <div>
                            <p class="font-semibold text-gray-900">{{ $issue->label }}</p>
                            @if ($issue->title)<p class="mt-0.5 text-sm text-gray-500">{{ $issue->title }}</p>@endif
                            <p class="mt-3 text-xs font-medium text-red-600">{{ $issue->articles_count }} artikel →</p>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada edisi terbit di jurnal ini.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
