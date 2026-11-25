@extends('layouts.app', ['title' => 'Pengaduan — Admin'])

@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Pengaduan &amp; Keberatan</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $openCount }} pengaduan belum selesai. Batas tindak lanjut {{ config('simpil.complaints.response_days') }} hari sejak diterima.</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (\App\Models\Complaint::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    @foreach (['Nomor', 'Judul', 'Pengadu', 'Jenis', 'Status', 'Batas'] as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($complaints as $complaint)
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-6 py-4 text-sm font-semibold whitespace-nowrap"><a href="{{ route('admin.complaints.show', $complaint) }}" class="text-red-600 hover:underline">{{ $complaint->ticket }}</a></td>
                        <td class="px-6 py-4 text-sm text-gray-800">{{ $complaint->subject }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $complaint->user?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $complaint->category_label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $complaint->status_label }}</td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap {{ $complaint->isOverdue() ? 'font-semibold text-red-600' : 'text-gray-500' }}">
                            {{ $complaint->due_at->format('d M Y') }}@if ($complaint->isOverdue()) · lewat batas @endif
                        </td>
                    </tr>
                @empty
                    @include('roles.editor.partials.empty-row', ['colspan' => 6, 'message' => 'Belum ada pengaduan.'])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $complaints->links() }}</div>
@endsection
