{{--
    Reusable Modal shell. Dipakai di dalam elemen ber-x-data="editorModal({...})" yang menyediakan:
    isOpen, loading, submitting, detail, errorMsg, successMsg, close().

    Props : title, subtitle, width (kelas max-w-*)
    Slot  : default = isi (tampil setelah detail termuat), $footer = tombol aksi
--}}
@props(['title', 'subtitle' => null, 'width' => 'max-w-3xl'])

@include('roles.editor.partials.editor-js')

<div x-show="isOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="close()">

    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="close()" class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-2xl shadow-2xl w-full {{ $width }} max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" @click="close()" aria-label="Tutup"
                    class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex-1 flex items-center justify-center py-20">
            <div class="flex flex-col items-center gap-3 text-gray-400">
                <svg class="animate-spin w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="text-sm">Memuat data...</span>
            </div>
        </div>

        {{-- Body --}}
        <div x-show="!loading && detail" class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
            {{ $slot }}
        </div>

        {{-- Pesan (juga tampil bila gagal memuat) --}}
        <div x-show="errorMsg || successMsg" class="px-6 pb-4 space-y-2 flex-shrink-0">
            <div x-show="errorMsg" x-text="errorMsg" role="alert"
                 class="px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>
            <div x-show="successMsg" x-text="successMsg" role="status"
                 class="px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg"></div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-shrink-0">
            <button type="button" @click="close()"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Tutup
            </button>
            <div x-show="!loading && detail" class="flex items-center gap-3">{{ $footer ?? '' }}</div>
        </div>
    </div>
</div>
