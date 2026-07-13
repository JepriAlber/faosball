@extends('layouts.app-auth')

@section('title', 'Verify Email')

@section('content')
    <div class="auth-split">

        {{-- ===== Kolom Kiri: Verify Email ===== --}}
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
                        Verify Email
                    </h1>
                    <p class="auth-subtitle">
                        Kami telah mengirim link verifikasi ke email Anda. Silakan cek inbox untuk melanjutkan.
                    </p>
                </div>

                {{-- Status message --}}
                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success">
                        <p class="alert-message">Link verifikasi baru telah dikirim ke email Anda.</p>
                    </div>
                @endif

                {{-- Info box --}}
                <div class="alert alert-info">
                    <p class="alert-message">
                        Sebelum melanjutkan, silakan verifikasi email Anda terlebih dahulu.
                        Jika belum menerima email, Anda dapat meminta pengiriman ulang.
                    </p>
                </div>

                {{-- Resend form --}}
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <button type="submit" class="btn btn-primary w-full">
                        Kirim Ulang Email Verifikasi
                    </button>
                </form>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf

                    <button type="submit" class="btn btn-secondary w-full">
                        Logout
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding (hanya tampil di lg ke atas) ===== --}}
        <x-auth-sidebar />

    </div>
@endsection
