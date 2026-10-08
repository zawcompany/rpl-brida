{{-- Tombol aksi teks pada baris tabel admin. Props: modal (nama modal), id, label, tone (default|danger|primary) --}}
@props(['modal', 'id', 'label', 'tone' => 'default'])
@php
    $classes = match ($tone) {
        'primary' => 'bg-red-600 hover:bg-red-700 text-white border-red-600',
        'danger'  => 'bg-white hover:bg-red-50 text-red-600 border-red-200',
        default   => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300',
    };
@endphp
<button type="button" onclick="openEditorModal('{{ $modal }}', {{ (int) $id }})"
        class="px-2.5 py-1.5 text-xs font-medium rounded-lg border transition-colors whitespace-nowrap {{ $classes }}">{{ $label }}</button>
