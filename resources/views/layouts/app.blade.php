<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        page: '{{ $page ?? 'dashboard' }}',
        loaded: true,
        darkMode: $persist(false).as('darkMode'),
        stickyMenu: false,
        sidebarToggle: false,
        scrollTop: false
    }"
    :class="{ 'dark': darkMode }"
>
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
<body class="bg-white dark:bg-gray-900">

    {{-- ===== Preloader ===== --}}
    @include('partials.preloader')

    {{-- ===== Page Wrapper ===== --}}
    <div class="flex h-screen overflow-hidden">

        {{-- ===== Sidebar ===== --}}
        @include('partials.sidebar')

        {{-- ===== Content Area ===== --}}
        <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto">

            {{-- Overlay mobile saat sidebar terbuka --}}
            <div
                @click="sidebarToggle = false"
                :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                class="fixed inset-0 z-[9] bg-gray-900/50"
            ></div>

            {{-- ===== Header ===== --}}
            @include('partials.header')

            {{-- ===== Main Content ===== --}}
            <main>
                <div class="p-4 mx-auto max-w-screen-2xl md:p-6">
                    @yield('content')
                </div>
            </main>

        </div>
        {{-- ===== Content Area End ===== --}}

    </div>
    {{-- ===== Page Wrapper End ===== --}}

    {{-- Stack untuk script tambahan per-halaman --}}
    @stack('scripts')

</body>
</html>