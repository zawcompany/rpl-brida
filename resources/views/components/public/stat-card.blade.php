{{-- Kartu statistik publik. Props: label, value. --}}
@props(['label', 'value'])

<div class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm">
    <p class="text-4xl font-bold text-red-600">{{ number_format((int) $value, 0, ',', '.') }}</p>
    <p class="mt-1 text-sm font-medium text-gray-500">{{ $label }}</p>
</div>
