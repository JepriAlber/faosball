@extends('layouts.app-auth')

@section('title', 'Verify Email')

@section('content')
    <div class="relative flex flex-col justify-center w-full min-h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">

        {{-- ===== Kolom Kiri: Verify Email ===== --}}
        <div class="flex flex-col flex-1 w-full lg:w-1/2">

            <div class="w-full max-w-md pt-10 mx-auto px-6">
                <a href="{{ route('login') }}"
                    class="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="stroke-current mr-2" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M12.7083 5L7.5 10.2083L12.7083 15.4167" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    Back to login
                </a>
            </div>

            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto px-6 py-12">

                {{-- Title --}}
                <div class="mb-5 sm:mb-8">
                    <h1 class="mb-2 font-semibold text-2xl text-gray-800 dark:text-white/90 sm:text-3xl">
                        Verify Email
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Kami telah mengirim link verifikasi ke email Anda. Silakan cek inbox untuk melanjutkan.
                    </p>
                </div>

                {{-- Status message --}}
                @if (session('status') == 'verification-link-sent')
                    <div
                        class="mb-5 p-4 bg-green-50 border border-green-200 rounded-lg dark:bg-green-900/20 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400">
                            Link verifikasi baru telah dikirim ke email Anda.
                        </p>
                    </div>
                @endif

                {{-- Info box --}}
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                    <p class="text-sm text-blue-700 dark:text-blue-300 leading-relaxed">
                        Sebelum melanjutkan, silakan verifikasi email Anda terlebih dahulu.
                        Jika belum menerima email, Anda dapat meminta pengiriman ulang.
                    </p>
                </div>

                {{-- Resend form --}}
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <button type="submit"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-white transition-colors rounded-lg bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        Kirim Ulang Email Verifikasi
                    </button>
                </form>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf

                    <button type="submit"
                        class="w-full px-4 py-3 text-sm font-semibold text-gray-600 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-800">
                        Logout
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding ===== --}}
        <div
            class="relative hidden w-full h-screen lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 dark:from-gray-800 dark:via-gray-900 dark:to-gray-900">

            {{-- Decorative background --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-64 h-64 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-80 h-80 rounded-full bg-indigo-300/20 blur-3xl"></div>
                <div
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full bg-blue-400/10 blur-3xl">
                </div>
            </div>

            <div class="relative flex flex-col items-center justify-center w-full px-16 text-center z-10">

                {{-- Icon --}}
                <div class="w-24 h-24 mb-8 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                        class="text-white">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>

                <h2 class="text-4xl font-bold text-white mb-3 tracking-tight">FAoSBall</h2>
                <p class="text-blue-200 text-lg font-medium mb-3">Email Verification System</p>
                <p class="text-blue-300/80 text-sm leading-relaxed max-w-sm mx-auto">
                    Verifikasi email memastikan keamanan akun dan akses penuh ke sistem FAoSBall.
                </p>

            </div>
        </div>

    </div>
@endsection
