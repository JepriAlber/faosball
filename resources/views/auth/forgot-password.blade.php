@extends('layouts.app-auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Form Forgot Password ===== --}}
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
                        Forgot Password
                    </h1>
                    <p class="auth-subtitle">
                        Masukkan email Anda untuk menerima link reset password.
                    </p>
                </div>

                {{-- Status --}}
                @if (session('status'))
                    <div class="alert alert-success">
                        <p class="alert-message">{{ session('status') }}</p>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-6">
                        <label class="form-label" for="email">
                            Email <span class="text-error-500">*</span>
                        </label>

                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="nama@example.com"
                            class="form-input @error('email') form-danger @enderror" />

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-primary w-full">
                        Kirim Link Reset Password
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
