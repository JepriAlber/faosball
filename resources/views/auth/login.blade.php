@extends('layouts.app-auth')

@section('title', 'Sign In')

@section('content')
    <div class="relative flex flex-col justify-center w-full min-h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">

        {{-- ===== Kolom Kiri: Form Login ===== --}}
        <div class="flex flex-col flex-1 w-full lg:w-1/2">
            <div class="w-full max-w-md pt-10 mx-auto px-6">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="stroke-current mr-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Back to dashboard
                </a>
            </div>

            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-6 py-12">

                {{-- Judul --}}
                <div class="mb-5 sm:mb-8">
                    <h1 class="mb-2 font-semibold text-2xl text-gray-800 dark:text-white/90 sm:text-3xl">
                        Sign In
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Masukkan email dan password untuk masuk ke sistem.
                    </p>
                </div>

                {{-- Error validasi --}}
                @if ($errors->any())
                    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Status session (setelah reset password/logout) --}}
                @if (session('status'))
                    <div
                        class="mb-5 p-4 bg-green-50 border border-green-200 rounded-lg dark:bg-green-900/20 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400">{{ session('status') }}</p>
                    </div>
                @endif

                {{-- Form Login --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-4">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="email">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="nama@example.com"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-blue-600 @error('email') border-red-500 dark:border-red-500 @enderror" />
                    </div>

                    {{-- Password --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-400" for="password">
                                Password <span class="text-red-500">*</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                    class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400">
                                    Lupa password?
                                </a>
                            @endif
                        </div>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pr-12 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-blue-600" />
                            {{-- Toggle show/hide password --}}
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors">
                                {{-- Eye (tampilkan) --}}
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                {{-- Eye Off (sembunyikan) --}}
                                <svg x-show="showPassword" style="display:none;" xmlns="http://www.w3.org/2000/svg"
                                    width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center mb-6">
                        <input id="remember_me" type="checkbox" name="remember"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900" />
                        <label for="remember_me" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Ingat saya
                        </label>
                    </div>

                    {{-- Tombol Submit --}}
                    <button type="submit"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-white transition-colors rounded-lg bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        Masuk ke Sistem
                    </button>
                    {{-- Link ke Register --}}
                    <p class="mt-6 text-sm text-center text-gray-600 dark:text-gray-400">
                        Belum punya akun?
                        <a href="{{ route('register') }}"
                            class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            Daftar sekarang
                        </a>
                    </p>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <div
            class="relative hidden w-full h-screen lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 dark:from-gray-800 dark:via-gray-900 dark:to-gray-900">

            {{-- Pattern dekoratif --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-64 h-64 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-80 h-80 rounded-full bg-indigo-300/20 blur-3xl"></div>
                <div
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full bg-blue-400/10 blur-3xl">
                </div>
            </div>

            <div class="relative flex flex-col items-center justify-center w-full px-16 text-center z-10">

                {{-- Logo Icon --}}
                <div
                    class="inline-flex items-center justify-center w-24 h-24 mb-8 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round" class="text-white">
                        <circle cx="12" cy="12" r="10" />
                        <path
                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                        <path d="M2 12h20" />
                    </svg>
                </div>

                {{-- Nama Aplikasi --}}
                <h2 class="text-4xl font-bold text-white mb-3 tracking-tight">FAoSBall</h2>
                <p class="text-blue-200 text-lg font-medium mb-3">Football Academy Operating System</p>
                <p class="text-blue-300/80 text-sm leading-relaxed max-w-sm mx-auto">
                    Platform manajemen akademi sepak bola yang terintegrasi — dari pemain hingga operasional, semua dalam
                    satu sistem.
                </p>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 mt-12 w-full max-w-sm">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">6</p>
                        <p class="text-blue-300 text-xs mt-1 font-medium">Roles</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">∞</p>
                        <p class="text-blue-300 text-xs mt-1 font-medium">Akademi</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                        <p class="text-2xl font-bold text-white">100%</p>
                        <p class="text-blue-300 text-xs mt-1 font-medium">Aman</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
