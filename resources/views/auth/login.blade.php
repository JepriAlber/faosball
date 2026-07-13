@extends('layouts.app-auth')

@section('title', 'Sign In')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Form Login ===== --}}
        <div class="auth-column">

            <div class="auth-form-container">

                {{-- Judul --}}
                <div class="auth-heading-group">
                    <h1 class="auth-title">
                        Sign In
                    </h1>
                    <p class="auth-subtitle">
                        Masukkan email dan password untuk masuk ke sistem.
                    </p>
                </div>

                {{-- Status session (setelah reset password/logout) --}}
                @if (session('status'))
                    <div class="alert alert-success">
                        <p class="alert-message">{{ session('status') }}</p>
                    </div>
                @endif

                {{-- Form Login --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email <span class="text-error-500">*</span>
                        </label>

                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="nama@example.com"
                            class="form-input @error('email') form-danger @enderror" />

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group">
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-800 dark:text-white/90" for="password">
                                Password <span class="text-error-500">*</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="link-primary text-sm">
                                    Lupa password?
                                </a>
                            @endif
                        </div>
                        <div class="relative" x-data="{ showPassword: false }">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="form-input pr-12 @error('password') form-danger @enderror" />
                            {{-- Toggle show/hide password --}}
                            <button type="button" @click="showPassword = !showPassword" class="auth-password-toggle">
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
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Remember Me --}}
                    <div class="mb-6 flex items-center">
                        <input id="remember_me" type="checkbox" name="remember" class="form-checkbox" />
                        <label for="remember_me" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                            Ingat saya
                        </label>
                    </div>

                    {{-- Tombol Submit --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Masuk ke Sistem
                    </button>

                    {{-- Link ke Register --}}
                    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
                        Belum punya akun?
                        <a href="{{ route('register') }}" class="link-primary font-semibold">
                            Daftar sekarang
                        </a>
                    </p>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
