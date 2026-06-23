@extends('layouts.app-auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="relative flex flex-col justify-center w-full min-h-screen dark:bg-gray-900 sm:p-0 lg:flex-row">

        {{-- ===== Kolom Kiri: Form Forgot Password ===== --}}
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
                        Forgot Password
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Masukkan email Anda untuk menerima link reset password.
                    </p>
                </div>

                {{-- Status --}}
                @if (session('status'))
                    <div
                        class="mb-5 p-4 bg-green-50 border border-green-200 rounded-lg dark:bg-green-900/20 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400">
                            {{ session('status') }}
                        </p>
                    </div>
                @endif

                {{-- Error --}}
                @if ($errors->any())
                    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-6">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="email">
                            Email <span class="text-red-500">*</span>
                        </label>

                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="nama@example.com"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring focus:ring-blue-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="flex items-center justify-center w-full px-4 py-3 text-sm font-semibold text-white transition-colors rounded-lg bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        Kirim Link Reset Password
                    </button>
                </form>

            </div>
        </div>

        {{-- ===== Kolom Kanan: Branding ===== --}}
        <div
            class="relative hidden w-full h-screen lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 dark:from-gray-800 dark:via-gray-900 dark:to-gray-900">

            {{-- Background decoration --}}
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
                        <path d="M12 2v20" />
                        <path d="M5 12h14" />
                    </svg>
                </div>

                <h2 class="text-4xl font-bold text-white mb-3 tracking-tight">FAoSBall</h2>
                <p class="text-blue-200 text-lg font-medium mb-3">Password Recovery</p>
                <p class="text-blue-300/80 text-sm leading-relaxed max-w-sm mx-auto">
                    Kami akan mengirimkan link reset password ke email Anda untuk memulihkan akses akun.
                </p>

            </div>
        </div>

    </div>
@endsection
