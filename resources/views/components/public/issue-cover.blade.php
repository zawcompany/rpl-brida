{{-- Gambar sampul edisi (placeholder bawaan bila belum diunggah). Prop: issue. Ukuran via class (default thumbnail 3:4). --}}
@props(['issue'])

<img src="{{ $issue->cover_url }}" alt="Sampul {{ $issue->label }}" loading="lazy"
     {{ $attributes->merge(['class' => 'h-28 w-[5.25rem] flex-shrink-0 rounded-lg border border-gray-200 bg-gray-100 object-cover']) }}>
