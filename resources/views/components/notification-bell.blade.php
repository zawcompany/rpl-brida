{{-- Ikon lonceng header + lencana jumlah notifikasi belum dibaca; tautan ke halaman Notifikasi. --}}
@php $unread = auth()->user()?->unreadNotifications()->count() ?? 0; @endphp
<a href="{{ route('notifications.index') }}"
   class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none transition-colors"
   title="Notifikasi" aria-label="Notifikasi{{ $unread ? ", {$unread} belum dibaca" : '' }}">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>
    @if ($unread > 0)
        <span class="absolute top-0.5 right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold leading-[1.1rem] text-center">{{ $unread > 99 ? '99+' : $unread }}</span>
    @endif
</a>
