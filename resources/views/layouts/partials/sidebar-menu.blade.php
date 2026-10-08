@php
    $role = Auth::user()->role ?? '';
@endphp

<div class="space-y-6">

    {{-- ========================================== --}}
    {{-- 1. ROLE: ADMINISTRATOR                    --}}
    {{-- ========================================== --}}
    @if($role === 'Administrator')
        <!-- KELOMPOK: PENGELOLAAN AKSES -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGELOLAAN AKSES
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Kelola Pengguna">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Kelola Pengguna</span>
                </a>
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Role & Hak Akses">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Role & Hak Akses</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: PENGATURAN -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGATURAN
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Profil Saya">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Profil Saya</span>
                </a>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- 2. ROLE: EDITOR                            --}}
    {{-- ========================================== --}}
    @elseif($role === 'Editor')
        <!-- KELOMPOK: PENGELOLAAN NASKAH -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGELOLAAN NASKAH
            </p>
            <div class="space-y-1">
                <a href="{{ route('editor.manuscripts.new') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('editor.manuscripts.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Naskah Baru">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Naskah Baru</span>
                </a>
                <a href="{{ route('editor.reviews.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('editor.reviews.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Peninjauan Naskah">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Peninjauan Naskah</span>
                </a>
                <a href="{{ route('editor.decisions.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('editor.decisions.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Keputusan Editorial">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Keputusan Editorial</span>
                </a>
            </div>
        </div>


        <!-- KELOMPOK: PUBLIKASI -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PUBLIKASI
            </p>
            <div class="space-y-1">
                <a href="{{ route('editor.issues.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('editor.issues.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Edisi & Publikasi">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Edisi & Publikasi</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: DIREKTORI -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                DIREKTORI
            </p>
            <div class="space-y-1">
                <a href="{{ route('editor.reviewers.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('editor.reviewers.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Direktori Reviewer">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Direktori Reviewer</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: PENGATURAN -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGATURAN
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Profil Saya">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Profil Saya</span>
                </a>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- 3. ROLE: AUTHOR                            --}}
    {{-- ========================================== --}}
    @elseif($role === 'Author')
        <!-- KELOMPOK: MANAJEMEN NASKAH -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                MANAJEMEN NASKAH
            </p>
            <div class="space-y-1">
                <a href="{{ route('author.manuscripts.create') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('author.manuscripts.create') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Naskah Baru">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Naskah Baru</span>
                </a>
                <a href="{{ route('author.manuscripts.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('author.manuscripts.index') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Naskah Saya">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Naskah Saya</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: INTERAKSI -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                INTERAKSI
            </p>
            <div class="space-y-1">
                <a href="{{ route('author.revisions.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('author.revisions.*') ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}" :class="!sidebarOpen && 'justify-center px-0'" title="Hasil Review & Revisi">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Hasil Review & Revisi</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: PENGATURAN -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGATURAN
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Profil Saya">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Profil Saya</span>
                </a>
            </div>
        </div>

    {{-- ========================================== --}}
    {{-- 4. ROLE: REVIEWER                          --}}
    {{-- ========================================== --}}
    @elseif($role === 'Reviewer')
        <!-- KELOMPOK: PENINJAUAN -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENINJAUAN
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Naskah Ditugaskan">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Naskah Ditugaskan</span>
                </a>
            </div>
        </div>

        <!-- KELOMPOK: PENGATURAN -->
        <div>
            <p x-show="sidebarOpen" class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                PENGATURAN
            </p>
            <div class="space-y-1">
                <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors" :class="!sidebarOpen && 'justify-center px-0'" title="Profil Saya">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span x-show="sidebarOpen" class="truncate">Profil Saya</span>
                </a>
            </div>
        </div>
    @endif

</div>