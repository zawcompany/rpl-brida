@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h1 class="text-xl font-bold text-gray-800">Dashboard Reviewer</h1>
            <p class="text-gray-500 text-sm mt-1">Daftar penelaahan naskah ilmiah yang ditugaskan kepada Anda.</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm w-full md:w-1/3">
            <p class="text-xs font-medium text-gray-400 uppercase">Tugas Review Aktif</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">3 Naskah</p>
        </div>
    </div>
@endsection