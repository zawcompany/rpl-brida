@extends('layouts.app')

@section('content')
@php
    $input = 'w-full text-sm border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white';
    $selectedFields = collect(old('research_field_ids', $user->researchFields->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
    $passwordErrors = $errors->getBag('password');
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
    <p class="text-sm text-gray-500 mt-1">Kelola data akun, keamanan, dan verifikasi email Anda.</p>
</div>

@if (session('success'))
    <div role="status" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif

{{-- KARTU IDENTITAS --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center gap-5">
        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-3xl font-semibold text-gray-600 flex-shrink-0">
            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold text-gray-900 truncate">{{ $user->name }}</h2>
            <p class="text-sm text-gray-600 truncate">{{ $user->email }}</p>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                <x-editor.badge :color="$user->role_badge_class">{{ $user->role_label }}</x-editor.badge>
                @if ($user->hasVerifiedEmail())
                    <x-editor.badge color="bg-green-100 text-green-700">Email terverifikasi</x-editor.badge>
                @else
                    <x-editor.badge color="bg-yellow-100 text-yellow-800">Email belum terverifikasi</x-editor.badge>
                @endif
                <span class="text-xs text-gray-400">Bergabung {{ $user->created_at->format('d M Y') }}</span>
            </div>
        </div>

        @unless ($user->hasVerifiedEmail())
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                    Kirim Email Verifikasi
                </button>
            </form>
        @endunless
    </div>
</div>

{{-- RINGKASAN PER ROLE --}}
@if (count($summary))
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ($summary as $item)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <p class="text-xs font-medium text-gray-500">{{ $item['label'] }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $item['value'] }}</p>
        </div>
    @endforeach
</div>
@endif

{{-- EDIT PROFIL --}}
<form method="POST" action="{{ route('profile.update') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6 mb-6">
    @csrf
    @method('PUT')
    <h2 class="text-base font-bold text-gray-900">Data Profil</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255"
                   class="{{ $input }} {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255"
                   class="{{ $input }} {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }}">
            <p class="text-xs text-gray-400 mt-1">Mengubah email akan meminta verifikasi ulang.</p>
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">Nomor Telepon</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" maxlength="30"
                   class="{{ $input }} {{ $errors->has('phone') ? 'border-red-400' : 'border-gray-300' }}">
            @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="institution" class="block text-sm font-semibold text-gray-700 mb-1.5">Instansi / Afiliasi</label>
            <input id="institution" name="institution" type="text" value="{{ old('institution', $user->institution) }}" maxlength="255"
                   class="{{ $input }} {{ $errors->has('institution') ? 'border-red-400' : 'border-gray-300' }}">
            @error('institution') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Hanya Reviewer: bidang keahlian (dasar rekomendasi penugasan oleh editor) --}}
    @if ($expertise)
    <div>
        <p class="text-sm font-semibold text-gray-700 mb-1.5">Bidang Keahlian <span class="font-normal text-gray-400">(maks. 10)</span></p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-56 overflow-y-auto border border-gray-200 rounded-lg p-3">
            @foreach ($expertise as $field)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="research_field_ids[]" value="{{ $field->id }}" @checked(in_array($field->id, $selectedFields, true))
                           class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    {{ $field->name }}
                </label>
            @endforeach
        </div>
        @error('research_field_ids') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        @error('research_field_ids.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>
    @endif

    <div class="flex justify-end pt-2 border-t border-gray-100">
        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors">Simpan Perubahan</button>
    </div>
</form>

{{-- UBAH PASSWORD --}}
<form method="POST" action="{{ route('profile.password') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
    @csrf
    @method('PUT')
    <div>
        <h2 class="text-base font-bold text-gray-900">Ubah Password</h2>
        <p class="text-xs text-gray-500">Minimal 8 karakter dan berbeda dari password saat ini.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password Saat Ini</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                   class="{{ $input }} {{ $passwordErrors->has('current_password') ? 'border-red-400' : 'border-gray-300' }}">
            @if ($passwordErrors->has('current_password')) <p class="text-xs text-red-600 mt-1">{{ $passwordErrors->first('current_password') }}</p> @endif
        </div>
        <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password Baru</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required
                   class="{{ $input }} {{ $passwordErrors->has('password') ? 'border-red-400' : 'border-gray-300' }}">
            @if ($passwordErrors->has('password')) <p class="text-xs text-red-600 mt-1">{{ $passwordErrors->first('password') }}</p> @endif
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Password Baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required
                   class="{{ $input }} border-gray-300">
        </div>
    </div>

    <div class="flex justify-end pt-2 border-t border-gray-100">
        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors">Ubah Password</button>
    </div>
</form>
@endsection
