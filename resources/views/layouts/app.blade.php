<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{
    page: '{{ $page ?? 'dashboard' }}',
    loaded: true,
    darkMode: $persist(false).as('darkMode'),
    stickyMenu: false,
    sidebarToggle: false,
    scrollTop: false
}" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Dashboard'))</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo/kantinit-favicon.png') }}">

    {{-- Vite: CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stack untuk CSS tambahan per-halaman --}}
    @stack('styles')
</head>

<body class="app-body">

    {{-- ===== Preloader ===== --}}
    @include('partials.preloader')

    {{-- ===== Page Wrapper ===== --}}
    <div class="page-wrapper">

        {{-- ===== Sidebar ===== --}}
        @include('partials.sidebar')

        {{-- ===== Content Area ===== --}}
        <div class="content-area">

            {{-- Overlay mobile saat sidebar terbuka --}}
            <div @click="sidebarToggle = false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                class="sidebar-overlay"></div>

            {{-- ===== Header ===== --}}
            @include('partials.header')

            {{-- ===== Main Content ===== --}}
            <main>
                <div class="content-container">

                    @yield('content')
                </div>
            </main>

        </div>
        {{-- ===== Content Area End ===== --}}

    </div>
    {{-- ===== Page Wrapper End ===== --}}

    {{-- ===== Modal Konfirmasi Logout ===== --}}
    <x-modal.logout />

    {{-- Stack untuk script tambahan per-halaman --}}
    @stack('scripts')

</body>

</html>
