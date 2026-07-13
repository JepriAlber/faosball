@extends('layouts.app-auth')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Form Reset Password ===== --}}
        <div class="auth-column">

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
                        Reset Password
                    </h1>
                    <p class="auth-subtitle">
                        Masukkan password baru untuk akun Anda.
                    </p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('password.store') }}">
                    @csrf

                    {{-- Token --}}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email <span class="text-error-500">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                            required readonly class="form-input form-disabled" />
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Password Baru <span class="text-error-500">*</span>
                        </label>
                        <input id="password" type="password" name="password" required
                            class="form-input @error('password') form-danger @enderror" />
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-6">
                        <label class="form-label" for="password_confirmation">
                            Konfirmasi Password <span class="text-error-500">*</span>
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="form-input" />
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Reset Password
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
