@extends('layouts.guest', ['title' => $journal->name . ' — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-6">
    <a href="{{ route('journals.index', \App\Support\Nav::keep()) }}" class="text-sm text-gray-500 hover:text-red-600">← Semua jurnal</a>

    <div class="mt-4 space-y-4">
        @if (session('success'))
            <div role="status" class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[17rem_minmax(0,1fr)] lg:gap-12">

        {{-- Cover besar (kiri) --}}
        <aside>
            <div class="mx-auto w-full max-w-[16rem] lg:sticky lg:top-28 lg:mx-0 lg:max-w-none">
                <x-public.journal-cover :journal="$journal" class="aspect-[3/4] w-full rounded-xl border border-gray-200 shadow-md" />
            </div>
        </aside>

        {{-- Deskripsi, template, dan edisi (kanan) --}}
        <div class="min-w-0 space-y-10">
            <header>
                @if ($journal->abbreviation)
                    <p class="text-xs font-semibold uppercase tracking-widest text-red-600">{{ $journal->abbreviation }}</p>
                @endif
                <h2 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $journal->name }}</h2>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    @if ($journal->field)
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ $journal->field }}</span>
                    @endif
                    @unless ($journal->is_active)
                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">Jurnal nonaktif</span>
                    @endunless
                </div>
                <p class="mt-4 max-w-3xl leading-relaxed text-gray-600">{{ $journal->description ?: 'Belum ada deskripsi jurnal.' }}</p>
            </header>

            {{-- Template naskah --}}
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="font-bold text-gray-900">Template Naskah</h3>

                @if ($journal->hasTemplate())
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $journal->template_original_name ?: 'Template bawaan jurnal' }}
                        @if ($journal->template_version > 0)<span class="text-gray-400">· versi {{ $journal->template_version }}</span>@endif
                    </p>
                @else
                    <p class="mt-1 text-sm text-gray-500">Jurnal ini belum mengunggah template.</p>
                @endif

                <div class="mt-4 space-y-3">
                    @guest
                        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                            Template hanya untuk anggota jurnal. <a href="{{ route('login') }}" class="font-semibold text-red-600 hover:underline">Masuk</a>
                            atau <a href="{{ route('register') }}" class="font-semibold text-red-600 hover:underline">daftar</a> sebagai penulis, lalu bergabung ke jurnal ini.
                        </div>
                    @else
                        @if ($isMember || auth()->user()->isAdmin())
                            @if ($journal->hasTemplate())
                                <a href="{{ route('journals.template', $journal) }}"
                                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
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
                                <button type="submit" class="rounded-lg bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
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
                </div>
            </section>

            {{-- Edisi terbit --}}
            <section>
                <h3 class="mb-4 font-bold text-gray-900">Edisi Terbit</h3>
                @if ($issues->isNotEmpty())
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 xl:grid-cols-4">
                        @foreach ($issues as $issue)
                            <x-public.issue-tile :issue="$issue" />
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">Belum ada edisi terbit di jurnal ini.</div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
