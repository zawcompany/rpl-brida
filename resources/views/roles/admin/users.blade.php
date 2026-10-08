@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kelola Pengguna</h1>
        <p class="text-sm text-gray-500 mt-1">Tambah, ubah, tangguhkan, dan reset password akun pengguna SIMPIL.</p>
    </div>
    <button type="button" onclick="openEditorModal('user-form', 0)"
            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Pengguna
    </button>
</div>

<x-admin.data-table
    :url="route('admin.users.index')"
    :headers="['Nama Pengguna', 'Email', 'Instansi / Keterangan', 'Role', 'Status Akun', 'Tanggal Mendaftar', 'Aksi']"
    :rows="$rows"
    rows-view="roles.admin.partials.user-table-rows"
    :roles="$roleOptions"
    :role="$initialRole"
    placeholder="Cari nama atau email..."
    unit="pengguna" />

@include('roles.admin.partials.user-form-modal')
@include('roles.admin.partials.user-confirm-modal')
@endsection
