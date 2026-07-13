@extends('layouts.app-auth')

@section('title', 'Sign Up')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Form Register ===== --}}
        <div class="auth-column">

            {{-- Back to login --}}
            <div class="auth-back-link-wrapper">
                <a href="{{ route('login') }}" class="auth-back-link">
                    <svg class="stroke-current mr-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Back to login
                </a>
            </div>

            <div class="auth-form-container">

                {{-- Judul --}}
                <div class="auth-heading-group">
                    <h1 class="auth-title">
                        Sign Up
                    </h1>
                    <p class="auth-subtitle">
                        Buat akun untuk mengakses sistem FAoSBall.
                    </p>
                </div>

                {{-- Form Register --}}
                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    {{-- Name --}}
                    <div class="form-group">
                        <label class="form-label" for="name">
                            Name <span class="text-error-500">*</span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required
                            placeholder="Full name" class="form-input @error('name') form-danger @enderror" />
                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email <span class="text-error-500">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            placeholder="nama@example.com" class="form-input @error('email') form-danger @enderror" />
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group" x-data="{ showPassword: false }">
                        <label class="form-label" for="password">
                            Password <span class="text-error-500">*</span>
                        </label>

                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                                placeholder="••••••••"
                                class="form-input pr-12 @error('password') form-danger @enderror" />

                            <button type="button" @click="showPassword = !showPassword" class="auth-password-toggle">
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
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

                    {{-- Confirm Password --}}
                    <div class="mb-6" x-data="{ showPassword: false }">
                        <label class="form-label" for="password_confirmation">
                            Confirm Password <span class="text-error-500">*</span>
                        </label>

                        <div class="relative">
                            <input id="password_confirmation" :type="showPassword ? 'text' : 'password'"
                                name="password_confirmation" required placeholder="••••••••"
                                class="form-input pr-12" />

                            <button type="button" @click="showPassword = !showPassword" class="auth-password-toggle">
                                <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
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

                    {{-- Tombol Submit --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Create Account
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
