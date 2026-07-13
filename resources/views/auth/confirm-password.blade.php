@extends('layouts.app-auth')

@section('title', 'Confirm Password')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Confirm Password ===== --}}
        <div class="auth-column">

            <div class="auth-back-link-wrapper">
                <a href="{{ route('dashboard') }}" class="auth-back-link">
                    <svg class="stroke-current mr-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Back
                </a>
            </div>

            <div class="auth-form-container">

                {{-- Judul --}}
                <div class="auth-heading-group">
                    <h1 class="auth-title">
                        Confirm Password
                    </h1>
                    <p class="auth-subtitle">
                        Konfirmasi password Anda sebelum melanjutkan ke halaman ini.
                    </p>
                </div>

                {{-- Info --}}
                <div class="alert alert-info">
                    <p class="alert-message">
                        Area ini bersifat sensitif. Silakan masukkan password Anda untuk melanjutkan.
                    </p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    {{-- Password --}}
                    <div class="mb-6">
                        <label class="form-label" for="password">
                            Password <span class="text-error-500">*</span>
                        </label>

                        <input id="password" type="password" name="password" required autofocus
                            autocomplete="current-password" placeholder="••••••••"
                            class="form-input @error('password') form-danger @enderror" />

                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Konfirmasi Password
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
