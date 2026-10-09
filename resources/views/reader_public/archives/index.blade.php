@extends('layouts.guest', ['title' => 'Arsip Terbitan — SIMPIL BRIDA'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">Arsip Terbitan</h2>
        <p class="mt-1 text-gray-500">Seluruh edisi yang telah diterbitkan, dikelompokkan per tahun.</p>
    </div>

    @forelse ($archives as $year => $issues)
        <section class="mb-8">
            <h3 class="mb-3 border-b border-gray-200 pb-2 text-lg font-bold text-gray-900">{{ $year }}</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($issues as $issue)
                    <a href="{{ route('archives.show', $issue) }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <p class="font-semibold text-gray-900">{{ $issue->label }}</p>
                        @if ($issue->title)<p class="mt-0.5 text-sm text-gray-500">{{ $issue->title }}</p>@endif
                        <p class="mt-3 text-xs font-medium text-red-600">{{ $issue->articles_count }} artikel →</p>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-12 text-center text-sm text-gray-500">Belum ada edisi yang diterbitkan.</div>
    @endforelse
</div>
@endsection
