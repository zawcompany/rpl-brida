{{-- Kartu statistik publik. Props: label, value. Saat disorot, kartu terangkat dan bayangannya menebal. --}}
@props(['label', 'value'])

<div class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm transition duration-200 ease-out hover:-translate-y-1 hover:shadow-lg motion-reduce:transition-none motion-reduce:hover:translate-y-0">
    <p class="text-4xl font-bold text-red-600">{{ number_format((int) $value, 0, ',', '.') }}</p>
    <p class="mt-1 text-sm font-medium text-gray-500">{{ $label }}</p>
</div>
