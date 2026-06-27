@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <div x-data="{ showDeleteModal: false, deleteAction: '', playerName: '' }">

        <!-- Breadcrumb Start -->
        <div x-data="{ pageName: @js($title) }">
            @include('partials.breadcrumb')
        </div>
        <!-- Breadcrumb End -->

        <!-- Alerts -->
        @include('partials.alert')
        <!-- Alerts End -->

        <div class="rounded-2xl border border-gray-200 bg-white">

            <!-- Table Header Actions -->
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100">

                <div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        Player List
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Manajemen data pemain akademi sepak bola.
                    </p>
                </div>

                <div>
                    <a href="{{ route('players.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-600">

                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Tambah Player
                    </a>
                </div>

            </div>


            <!-- Table Content -->
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[1100px] table-auto text-left">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500">
                                Info Player
                            </th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500">
                                Profil
                            </th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500">
                                Posisi
                            </th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500">
                                Status
                            </th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 text-right">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($players as $player)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center">

                                            @if ($player->photo && Storage::disk('public')->exists($player->photo))
                                                <img src="{{ asset('storage/' . $player->photo) }}"
                                                    alt="{{ $player->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="font-bold text-gray-400 text-lg">
                                                    {{ Str::upper(Str::substr($player->name ?? 'P', 0, 2)) }}
                                                </span>
                                            @endif

                                        </div>
                                        <div>
                                            <a href="{{ route('players.show', $player->id_player) }}"
                                                class="block font-medium text-gray-800 hover:text-brand-500 text-theme-sm">
                                                {{ $player->name }}
                                            </a>
                                            <span class="block text-gray-400 text-theme-xs mt-0.5">
                                                {{ $player->player_code }}

                                                @if ($player->nick_name)
                                                    - {{ $player->nick_name }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="block text-theme-sm text-gray-700">
                                        {{ $player->birth_date ? \Carbon\Carbon::parse($player->birth_date)->format('d M Y') : '-' }}

                                    </span>

                                    <span class="block text-theme-xs text-gray-400 mt-0.5">
                                        {{ ucfirst($player->gender ?? '-') }}
                                        @if ($player->nationality)
                                            - {{ $player->nationality }}
                                        @endif
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="block text-theme-sm text-gray-700">
                                        {{ $player->primary_position ?? '-' }}
                                    </span>
                                    <span class="block text-theme-xs text-gray-400 mt-0.5">
                                        {{ $player->secondary_position ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    @if ($player->status)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-theme-xs font-medium text-green-700">
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-theme-xs font-medium text-red-700">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2.5">
                                        {{-- Detail --}}

                                        <a href="{{ route('players.show', $player->id_player) }}"
                                            class="text-gray-500 hover:text-brand-500" title="Detail">

                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">

                                                <path
                                                    d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                                <path
                                                    d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                            </svg>
                                        </a>

                                        {{-- Edit --}}

                                        <a href="{{ route('players.edit', $player->id_player) }}"
                                            class="text-gray-500 hover:text-yellow-500" title="Edit">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">

                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>

                                        </a>


                                        {{-- Buat Akun --}}

                                        @if (!$player->id_user)
                                            <a href="{{ route('players.account.create', $player->id_player) }}"
                                                class="text-gray-500 hover:text-green-500" title="Buat Akun">

                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">

                                                    <path
                                                        d="M10 10C12.0711 10 13.75 8.32107 13.75 6.25C13.75 4.17893 12.0711 2.5 10 2.5C7.92893 2.5 6.25 4.17893 6.25 6.25C6.25 8.32107 7.92893 10 10 10Z"
                                                        stroke="currentColor" stroke-width="1.5" />

                                                    <path
                                                        d="M3.75 17.5C3.75 14.7386 6.54822 12.5 10 12.5C13.4518 12.5 16.25 14.7386 16.25 17.5"
                                                        stroke="currentColor" stroke-width="1.5" />

                                                </svg>

                                            </a>
                                        @endif
                                        {{-- Delete --}}

                                        <button type="button"
                                            @click="deleteAction = '{{ route('players.destroy', $player->id_player) }}'; playerName = '{{ addslashes($player->name) }}'; showDeleteModal = true"
                                            class="text-gray-500 hover:text-red-500" title="Hapus">

                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">

                                                <path
                                                    d="M3.75 5H16.25M7.5 5V3.75C7.5 3.41848 7.6317 3.10054 7.86612 2.86612C8.10054 2.6317 8.41848 2.5 8.75 2.5H11.25C11.5815 2.5 11.8995 2.6317 12.1339 2.86612C12.3683 3.10054 12.5 3.41848 12.5 3.75V5M14.375 5V16.25C14.375 16.5815 14.2433 16.8995 14.0089 17.1339C13.7745 17.3683 13.4565 17.5 13.125 17.5H6.875C6.54348 17.5 6.22554 17.3685 5.99112 17.1339C5.7567 16.8995 5.625 16.5815 5.625 16.25V5H14.375Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />

                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        @empty


                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" class="text-gray-300 mb-3">

                                            <path
                                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>

                                        <span class="block text-gray-500 text-sm font-medium">
                                            Belum ada data Player
                                        </span>

                                        <a href="{{ route('players.create') }}"
                                            class="mt-3 text-xs text-brand-500 hover:text-brand-600 font-semibold underline">
                                            Tambah sekarang
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            <!-- Pagination -->

            @if ($players->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $players->links() }}
                </div>
            @endif


        </div>



        <!-- Delete Confirmation Modal -->

        <div x-show="showDeleteModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>


            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-gray-100"
                @click.away="showDeleteModal = false" x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">


                <!-- Icon & Header -->

                <div class="flex items-center gap-4">

                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 flex-shrink-0">

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path d="M12 9V14M12 17.01L12.01 16.9989M12 3L2 21H22L12 3Z" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

                        </svg>

                    </span>


                    <div>

                        <h3 class="text-lg font-bold text-gray-800">
                            Konfirmasi Hapus
                        </h3>

                        <p class="text-sm text-gray-500">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                    </div>

                </div>



                <!-- Body Message -->

                <div class="mt-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Apakah Anda yakin ingin menghapus data Player
                        <strong class="text-gray-800" x-text="playerName"></strong>?
                        Semua data terkait pemain ini akan dihapus secara permanen.
                    </p>
                </div>

                <!-- Footer Buttons -->
                <div class="mt-6 flex items-center justify-end gap-3">

                    <button type="button" @click="showDeleteModal = false"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        Batal
                    </button>

                    <form :action="deleteAction" method="POST" class="inline">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                            Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
