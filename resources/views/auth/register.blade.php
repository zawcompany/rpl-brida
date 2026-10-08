@extends('layouts.guest', ['title' => 'Register - SIMPIL'])

@section('content')
<div class="min-h-screen w-full bg-gray-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
    
    <!-- Kartu Utama (Centered Split Card) -->
    <div class="w-full max-w-4xl lg:max-w-5xl bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2 min-h-[580px] border border-gray-100">
        
        <!-- Sisi Kiri (Branding & Ilustrasi) -->
        <div class="hidden md:flex bg-gray-50/80 p-8 lg:p-10 flex-col justify-between border-r border-gray-100/80 relative">
            
            <!-- Ornamen lengkungan -->
            <div class="absolute top-[-20%] left-[-20%] w-[25rem] h-[25rem] bg-red-100 rounded-full opacity-40 blur-[80px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[20rem] h-[20rem] bg-red-100 rounded-full opacity-30 blur-[80px]"></div>

            <div class="relative z-10">
                <img src="{{ asset('images/logo-brida.png') }}" alt="Logo BRIDA" class="h-9 w-auto mb-6" onerror="this.src='https://via.placeholder.com/120x40?text=Logo+BRIDA'">
                
                <h1 class="text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">SIMPIL</h1>
                <h2 class="text-sm lg:text-base text-gray-600 font-medium mt-1 leading-snug">
                    Sistem Informasi Manajemen Publikasi Ilmiah
                </h2>
                <div class="w-10 h-1 bg-red-600 rounded-full mt-3"></div>
            </div>

            <!-- Ilustrasi -->
            <div class="relative z-10 flex justify-center items-center my-auto pt-6 pb-2">
                <img src="{{ asset('images/ilus-auth.png') }}" alt="Ilustrasi SIMPIL" class="w-full max-w-[280px] sm:max-w-[320px] lg:max-w-[360px] h-auto object-contain drop-shadow-sm" onerror="this.style.display='none'">
            </div>
        </div>

        <!-- Sisi Kanan (Form Interaktif) -->
        <div class="p-8 lg:p-12 flex flex-col justify-center w-full relative z-10 bg-white">
            
            <!-- Tampil di Mobile saja -->
            <div class="md:hidden flex flex-col items-center text-center space-y-3 mb-6">
                <img src="{{ asset('images/logo-brida.png') }}" alt="Logo BRIDA" class="h-10" onerror="this.src='https://via.placeholder.com/120x40?text=Logo+BRIDA'">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">SIMPIL</h1>
                    <div class="w-8 h-1 bg-red-600 rounded-full mx-auto mt-1"></div>
                </div>
            </div>

            <!-- Header Form -->
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Buat Akun Baru</h3>
                <p class="text-xs sm:text-sm text-gray-400 mt-1">Daftar sebagai Penulis (Author) untuk mengajukan naskah ilmiah.</p>
            </div>

            <!-- Pesan Error / Validasi -->
            @if ($errors->any())
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg mb-4">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                
                <input type="hidden" name="role" value="Author">
                
                <!-- Input Nama Lengkap -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <!-- User Icon -->
                            <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="6" r="3.5" />
                                <path d="M10 12c-4.5 0-7 3-7 5h14c0-2-2.5-5-7-5z" />
                            </svg>
                        </div>
                        <input type="text" name="name" id="name" required autofocus placeholder="Masukkan nama lengkap"
                            class="block w-full pl-10 pr-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-red-600 bg-gray-50 focus:bg-white transition-colors" value="{{ old('name') }}">
                    </div>
                </div>

                <!-- Input Alamat Email -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <!-- Envelope Icon -->
                            <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                        </div>
                        <input type="email" name="email" id="email" required placeholder="Masukkan alamat email aktif"
                            class="block w-full pl-10 pr-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-red-600 bg-gray-50 focus:bg-white transition-colors" value="{{ old('email') }}">
                    </div>
                </div>

                <!-- Input Password -->
                <div x-data="{ show: false }" class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <!-- Padlock Icon -->
                            <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 20 20">
                                <rect x="5" y="8" width="10" height="9" rx="2" />
                                <path fill-rule="evenodd" d="M7 8V6a3 3 0 116 0v2H7zm2-2a1 1 0 112 0v2H9V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input :type="show ? 'text' : 'password'" name="password" id="password" required placeholder="Buat password"
                            class="block w-full pl-10 pr-10 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-red-600 bg-gray-50 focus:bg-white transition-colors">
                        
                        <!-- Toggle Show/Hide -->
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center focus:outline-none text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" x-cloak class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Input Konfirmasi Password -->
                <div x-data="{ show: false }" class="space-y-1.5">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <!-- Padlock Icon -->
                            <svg class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 20 20">
                                <rect x="5" y="8" width="10" height="9" rx="2" />
                                <path fill-rule="evenodd" d="M7 8V6a3 3 0 116 0v2H7zm2-2a1 1 0 112 0v2H9V6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required placeholder="Ulangi password"
                            class="block w-full pl-10 pr-10 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-600 focus:border-red-600 bg-gray-50 focus:bg-white transition-colors">
                        
                        <!-- Toggle Show/Hide -->
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center focus:outline-none text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" x-cloak class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-3">
                    <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 active:scale-[0.98]">
                        Daftar Akun
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="pt-2">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs sm:text-sm">
                        <span class="px-4 bg-white text-gray-400 font-medium">Sudah memiliki akun?</span>
                    </div>
                </div>
            </div>

            <!-- Masuk Button -->
            <div>
                <a href="{{ route('login') }}" class="w-full flex justify-center items-center py-2.5 px-4 border border-red-500 text-red-600 hover:bg-red-50 rounded-lg font-semibold text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 active:scale-[0.98]">
                    Masuk
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
