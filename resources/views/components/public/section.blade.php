{{-- Blok section halaman publik. Props: id (anchor), title, subtitle, tone (white|gray). --}}
@props(['id' => null, 'title', 'subtitle' => null, 'tone' => 'gray'])

<section @if ($id) id="{{ $id }}" @endif class="scroll-mt-24 py-14 {{ $tone === 'white' ? 'bg-white border-y border-gray-100' : '' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-2 text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $slot }}
    </div>
</section>
