{{-- Cover jurnal; bila belum diunggah, sampul gradasi merah berisi singkatan jurnal. Prop: journal. Ukuran lewat class (mis. aspect-[3/4] w-full). --}}
@props(['journal'])

@if ($journal->cover_url)
    <img src="{{ $journal->cover_url }}" alt="Cover {{ $journal->name }}" loading="lazy"
         {{ $attributes->merge(['class' => 'object-cover bg-gray-100']) }}>
@else
    <div role="img" aria-label="Cover {{ $journal->name }}"
         {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br from-red-500 via-red-600 to-red-800 text-white [container-type:inline-size]']) }}>
        <div class="absolute -right-[18cqw] -top-[18cqw] h-[60cqw] w-[60cqw] rounded-full bg-white/10"></div>
        <div class="absolute -bottom-[22cqw] -left-[14cqw] h-[70cqw] w-[70cqw] rounded-full bg-black/10"></div>
        <div class="relative flex h-full w-full items-center justify-center">
            <span class="text-[22cqw] font-extrabold leading-none tracking-wide">{{ $journal->initials }}</span>
        </div>
    </div>
@endif
