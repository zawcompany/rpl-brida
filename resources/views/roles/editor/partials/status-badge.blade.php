{{--
    Reusable: Badge Status Naskah
    Props: $status (string), $label (string)
--}}
@php
    $badgeClass = match($status) {
        'pending', 'pemeriksaan_awal' => 'bg-yellow-100 text-yellow-800',
        'ditinjau'                    => 'bg-blue-100 text-blue-800',
        'menunggu_keputusan'          => 'bg-purple-100 text-purple-800',
        'revisi'                      => 'bg-orange-100 text-orange-800',
        'disetujui'                   => 'bg-green-100 text-green-800',
        'ditolak'                     => 'bg-red-100 text-red-800',
        'diterbitkan'                 => 'bg-gray-100 text-gray-700',
        default                       => 'bg-gray-100 text-gray-600',
    };
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
    {{ $label }}
</span>
