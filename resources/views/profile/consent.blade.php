@extends('layouts.app', ['title' => 'Persetujuan Kebijakan — SIMPIL'])

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900">Persetujuan Kebijakan Privasi</h1>
    <p class="mt-2 text-sm text-gray-600">
        @if ($user->consent_version)
            Kebijakan Privasi dan Syarat Penggunaan SIMPIL telah diperbarui (versi {{ config('simpil.legal.version') }}).
            Mohon baca dan setujui versi terbaru untuk melanjutkan.
        @else
            Akun Anda belum memiliki catatan persetujuan atas Kebijakan Privasi dan Syarat Penggunaan SIMPIL.
            Mohon baca dan setujui untuk melanjutkan.
        @endif
    </p>

    @if ($errors->any())
        <div role="alert" class="mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('consent.accept') }}" class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
        @csrf
        <label class="flex items-start gap-3 text-sm text-gray-700">
            <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
            <span>
                Saya telah membaca dan menyetujui
                <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-red-600 hover:underline">Kebijakan Privasi</a> dan
                <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-red-600 hover:underline">Syarat Penggunaan</a> SIMPIL.
            </span>
        </label>
        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Setuju &amp; Lanjutkan</button>
        </div>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="text-sm text-gray-500 hover:text-red-600">Tidak setuju, keluar</button>
    </form>
</div>
@endsection
