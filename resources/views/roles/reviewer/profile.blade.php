@extends('layouts.app')

@section('content')

@php
    $user = Auth::user();
@endphp

<div class="min-h-screen bg-gray-50">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Profil Saya
        </h1>
    </div>

    {{-- KARTU PROFIL --}}
    <div class="rounded-lg bg-white p-8 shadow-sm">

        {{-- FOTO PROFIL, NAMA, DAN EMAIL --}}
        <div class="mb-8 flex flex-col items-center text-center">

            {{-- FOTO PROFIL --}}
            <div class="mb-3 flex h-32 w-32 items-center justify-center overflow-hidden rounded-full bg-gray-400">

                @if ($user->profile_photo_path ?? false)
                    <img
                        src="{{ asset('storage/' . $user->profile_photo_path) }}"
                        alt="Foto profil"
                        class="h-full w-full object-cover">
                @else
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-20 w-20 text-white"
                        viewBox="0 0 24 24"
                        fill="currentColor">
                        <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.42 0-8 2.24-8 5v3h16v-3c0-2.76-3.58-5-8-5Z"/>
                    </svg>
                @endif

            </div>

            <h2 class="text-xl font-medium text-gray-900">
                {{ $user->name }}
            </h2>

            <p class="text-sm text-gray-700">
                {{ $user->email }}
            </p>

        </div>

        {{-- INFORMASI PROFIL --}}
        <div class="mb-10 space-y-3 text-sm text-gray-900">

            <p>
                <span class="font-semibold">Nama Lengkap:</span>
                {{ $user->name }}
            </p>

            <p>
                <span class="font-semibold">Bidang Keahlian:</span>
                {{ $user->expertise ?? $user->bidang_keahlian ?? '-' }}
            </p>

            <p>
                <span class="font-semibold">Institusi:</span>
                {{ $user->institution ?? $user->institusi ?? '-' }}
            </p>

        </div>

        {{-- TOMBOL AKSI --}}
        <div class="flex flex-wrap gap-4">

            <button
                type="button"
                onclick="window.location.href='#edit-profil'"
                class="rounded-lg border border-gray-900 bg-gray-200 px-8 py-3 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-300">
                Edit Profil
            </button>

            <button
                type="button"
                onclick="window.location.href='#ubah-password'"
                class="rounded-lg border border-gray-900 bg-gray-200 px-8 py-3 text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-300">
                Ubah Password
            </button>

        </div>

    </div>

</div>

@endsection
