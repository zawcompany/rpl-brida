{{-- Badge pil reusable. Prop: color (kelas Tailwind bg/text). Isi = label. --}}
@props(['color' => 'bg-gray-100 text-gray-700'])
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$color}"]) }}>{{ $slot }}</span>
