@extends('layouts.app')

@section('content')
@php $input = 'w-full text-sm border rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white'; @endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
    <p class="text-sm text-gray-500 mt-1">Perbarui nama, email, dan password akun administrator Anda.</p>
</div>

@if (session('success'))
    <div role="status" class="mb-5 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.profile.update') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6 w-full">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255"
                   class="{{ $input }} {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300' }}">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255"
                   class="{{ $input }} {{ $errors->has('email') ? 'border-red-400' : 'border-gray-300' }}">
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="border-t border-gray-100 pt-6">
        <h2 class="text-sm font-bold text-gray-900">Ubah Password</h2>
        <p class="text-xs text-gray-500 mb-4">Kosongkan bila tidak ingin mengganti password.</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password Saat Ini</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                       class="{{ $input }} {{ $errors->has('current_password') ? 'border-red-400' : 'border-gray-300' }}">
                @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password Baru</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password"
                       class="{{ $input }} {{ $errors->has('password') ? 'border-red-400' : 'border-gray-300' }}">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Password Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password"
                       class="{{ $input }} border-gray-300">
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-2 border-t border-gray-100">
        <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm transition-colors">Simpan Perubahan</button>
    </div>
</form>
@endsection
