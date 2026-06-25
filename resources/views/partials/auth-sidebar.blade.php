<div
    class="relative hidden w-full h-screen lg:flex lg:w-1/2 bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 dark:from-gray-800 dark:via-gray-900 dark:to-gray-900">

    {{-- Pattern dekoratif --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-64 h-64 rounded-full bg-white/20 blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-80 h-80 rounded-full bg-indigo-300/20 blur-3xl"></div>
        <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full bg-blue-400/10 blur-3xl">
        </div>
    </div>

    <div class="relative flex flex-col items-center justify-center w-full px-16 text-center z-10">

        {{-- Logo Icon --}}
        <div
            class="inline-flex items-center justify-center w-24 h-24 mb-8 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-xl">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                class="text-white">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                <path d="M2 12h20" />
            </svg>
        </div>

        {{-- Nama Aplikasi --}}
        <h2 class="text-4xl font-bold text-white mb-3 tracking-tight">FAoSBall</h2>
        <p class="text-blue-200 text-lg font-medium mb-3">Football Academy Operating System</p>
        <p class="text-blue-300/80 text-sm leading-relaxed max-w-sm mx-auto">
            Platform manajemen akademi sepak bola yang terintegrasi — dari pemain hingga operasional, semua dalam
            satu sistem.
        </p>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-4 mt-12 w-full max-w-sm">
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                <p class="text-2xl font-bold text-white">6</p>
                <p class="text-blue-300 text-xs mt-1 font-medium">Roles</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                <p class="text-2xl font-bold text-white">∞</p>
                <p class="text-blue-300 text-xs mt-1 font-medium">Akademi</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-4 text-center">
                <p class="text-2xl font-bold text-white">100%</p>
                <p class="text-blue-300 text-xs mt-1 font-medium">Aman</p>
            </div>
        </div>

    </div>
</div>
