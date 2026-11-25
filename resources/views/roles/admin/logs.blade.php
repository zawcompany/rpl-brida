@extends('layouts.app', ['title' => 'Log Aktivitas — Admin'])

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Log Aktivitas</h1>
        <p class="text-sm text-gray-500 mt-1">Jejak audit seluruh kegiatan penting di SIMPIL (PP 71/2019 Pasal 22).</p>
    </div>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kegiatan atau pelaku" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white">
        <select name="action" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white">
            <option value="">Semua kegiatan</option>
            @foreach ($groups as $value => $label)
                <option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Terapkan</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Waktu', 'Pelaku', 'Kegiatan', 'Alamat IP'] as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td class="px-6 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $log->actor?->name ?? 'Sistem / tamu' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-800">{{ $log->description }}</td>
                        <td class="px-6 py-3 text-xs text-gray-400 whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    @include('roles.editor.partials.empty-row', ['colspan' => 4, 'message' => 'Tidak ada kegiatan yang cocok.'])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
