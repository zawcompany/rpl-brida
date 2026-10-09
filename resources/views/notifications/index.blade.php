@extends('layouts.app')

@section('content')
@php $types = \App\Notifications\WorkflowNotification::TYPES; @endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Notifikasi</h1>
        <p class="text-sm text-gray-500 mt-1">Pembaruan alur kerja naskah yang berkaitan dengan akun Anda.</p>
    </div>
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Tandai Semua Dibaca
            </button>
        </form>
    @endif
</div>

@if (session('success'))
    <div role="status" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif

{{-- Filter --}}
<div class="flex gap-2 mb-4">
    <a href="{{ route('notifications.index') }}"
       class="px-3 py-1.5 text-sm rounded-lg {{ ! $unreadOnly ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">Semua</a>
    <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
       class="px-3 py-1.5 text-sm rounded-lg {{ $unreadOnly ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
        Belum Dibaca @if ($unreadCount > 0)<span class="ml-1 text-xs font-semibold">({{ $unreadCount }})</span>@endif
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <ul class="divide-y divide-gray-100">
        @forelse ($rows as $n)
        @php
            $unread = $n->read_at === null;
            [$label, $badge] = $types[$n->data['type'] ?? ''] ?? ['Info', 'bg-gray-100 text-gray-700'];
        @endphp
        <li class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 {{ $unread ? 'bg-red-50/40' : '' }}">
            <span class="mt-1.5 sm:mt-0 w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $unread ? 'bg-red-500' : 'bg-gray-200' }}"
                  title="{{ $unread ? 'Belum dibaca' : 'Sudah dibaca' }}"></span>

            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <x-editor.badge :color="$badge">{{ $label }}</x-editor.badge>
                    <p class="text-sm {{ $unread ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }}">{{ $n->data['title'] ?? 'Notifikasi' }}</p>
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ $n->data['message'] ?? '' }}</p>
                <p class="text-xs text-gray-400 mt-1" title="{{ $n->created_at->format('d M Y H:i') }}">{{ $n->created_at->diffForHumans() }}</p>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                @if (! empty($n->data['url']))
                    <form method="POST" action="{{ route('notifications.open', $n->id) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Buka</button>
                    </form>
                @endif
                @if ($unread)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors">Tandai Dibaca</button>
                    </form>
                @endif
            </div>
        </li>
        @empty
        <li class="px-6 py-16 text-center text-sm text-gray-400">
            {{ $unreadOnly ? 'Tidak ada notifikasi yang belum dibaca.' : 'Belum ada notifikasi.' }}
        </li>
        @endforelse
    </ul>

    @if ($rows->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 text-sm">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
