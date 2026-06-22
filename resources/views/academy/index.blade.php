@extends('layouts.app', ['page' => 'academy'])

@section('title', 'Manajemen Academy - ' . config('app.name'))

@section('content')
    <div x-data="{ showDeleteModal: false, deleteAction: '', academyName: '' }">
        <!-- Breadcrumb Start -->
        <div x-data="{ pageName: 'Daftar Academy' }">
            @include('partials.breadcrumb')
        </div>
        <!-- Breadcrumb End -->

        <!-- Alerts -->
        @if (session('success'))
            <div
                class="mb-6 flex w-full items-center justify-between rounded-xl bg-green-50 p-4 border border-green-200 dark:bg-green-500/15 dark:border-green-500/30">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-500">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M16.7071 5.29289C17.0976 5.68342 17.0976 6.31658 16.7071 6.70711L8.70711 14.7071C8.31658 15.0976 7.68342 15.0976 7.29289 14.7071L3.29289 10.7071C2.90237 10.3166 2.90237 9.68342 3.29289 9.29289C3.68342 8.90237 4.31658 8.90237 4.70711 9.29289L8 12.5858L15.2929 5.29289C15.6834 4.90237 16.3166 4.90237 16.7071 5.29289Z"
                                fill="currentColor" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-green-800 dark:text-green-400">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div
                class="mb-6 flex w-full items-center justify-between rounded-xl bg-red-50 p-4 border border-red-200 dark:bg-red-500/15 dark:border-red-500/30">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-500">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M4.29289 4.29289C4.68342 3.90237 5.31658 3.90237 5.70711 4.29289L10 8.58579L14.2929 4.29289C14.6834 3.90237 15.3166 3.90237 15.7071 4.29289C16.0976 4.68342 16.0976 5.31658 15.7071 5.70711L11.4142 10L15.7071 14.2929C16.0976 14.6834 16.0976 15.3166 15.7071 15.7071C15.3166 16.0976 14.6834 16.0976 14.2929 15.7071L10 11.4142L5.70711 15.7071C5.31658 16.0976 4.68342 16.0976 4.29289 15.7071C3.90237 15.3166 3.90237 14.6834 4.29289 14.2929L8.58579 10L4.29289 5.70711C3.90237 5.31658 3.90237 4.68342 4.29289 4.29289Z"
                                fill="currentColor" />
                        </svg>
                    </span>
                    <p class="text-sm font-medium text-red-800 dark:text-red-400">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Table Header Actions -->
            <div
                class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Academy List</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manajemen profil, tagline, dan status akademi
                        sepak bola.</p>
                </div>
                <div>
                    <a href="{{ route('academy.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-600">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Tambah Academy
                    </a>
                </div>
            </div>

            <!-- Table Content -->
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[1000px] table-auto text-left">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-white/[0.01]">
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Info Academy
                            </th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Kontak</th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Tagline</th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-5 py-4 text-theme-xs font-semibold text-gray-500 dark:text-gray-400 text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($academies as $academy)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-gray-800 flex items-center justify-center">
                                            @if ($academy->logo)
                                                <img src="{{ asset('storage/' . $academy->logo) }}"
                                                    alt="Logo {{ $academy->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="font-bold text-gray-400 dark:text-gray-600 text-lg">
                                                    {{ strtoupper(substr($academy->name, 0, 2)) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('academy.show', $academy->id_academy) }}"
                                                class="block font-medium text-gray-800 hover:text-brand-500 text-theme-sm dark:text-white/90 dark:hover:text-brand-400">
                                                {{ $academy->name }}
                                            </a>
                                            <span class="block text-gray-400 text-theme-xs mt-0.5">
                                                {{ $academy->slug }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="block text-theme-sm text-gray-700 dark:text-gray-300">{{ $academy->email }}</span>
                                    <span class="block text-theme-xs text-gray-400 mt-0.5">{{ $academy->phone }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="text-theme-sm text-gray-600 dark:text-gray-400 line-clamp-1 italic">"{{ $academy->tagline }}"</span>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($academy->status)
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-2.5 py-0.5 text-theme-xs font-medium text-green-700 dark:bg-green-500/15 dark:text-green-500">
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-red-50 px-2.5 py-0.5 text-theme-xs font-medium text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2.5">
                                        <a href="{{ route('academy.show', $academy->id_academy) }}"
                                            class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400"
                                            title="Detail">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                                <path
                                                    d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('academy.edit', $academy->id_academy) }}"
                                            class="text-gray-500 hover:text-yellow-500 dark:text-gray-400 dark:hover:text-yellow-500"
                                            title="Edit">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                        <button type="button"
                                            @click="deleteAction = '{{ route('academy.destroy', $academy->id_academy) }}'; academyName = '{{ addslashes($academy->name) }}'; showDeleteModal = true"
                                            class="text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-500"
                                            title="Hapus">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M3.75 5H16.25M7.5 5V3.75C7.5 3.41848 7.6317 3.10054 7.86612 2.86612C8.10054 2.6317 8.41848 2.5 8.75 2.5H11.25C11.5815 2.5 11.8995 2.6317 12.1339 2.86612C12.3683 3.10054 12.5 3.41848 12.5 3.75V5M14.375 5V16.25C14.375 16.5815 14.2433 16.8995 14.0089 17.1339C13.7745 17.3683 13.4565 17.5 13.125 17.5H6.875C6.54348 17.5 6.22554 17.3683 5.99112 17.1339C5.7567 16.8995 5.625 16.5815 5.625 16.25V5H14.375Z"
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
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-gray-300 dark:text-gray-700 mb-3">
                                            <path
                                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <span class="block text-gray-500 dark:text-gray-400 text-sm font-medium">Belum ada
                                            data Academy</span>
                                        <a href="{{ route('academy.create') }}"
                                            class="mt-3 text-xs text-brand-500 hover:text-brand-600 dark:text-brand-400 font-semibold underline">Tambah
                                            sekarang</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($academies->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $academies->links() }}
                </div>
            @endif
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800"
                @click.away="showDeleteModal = false" x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                <!-- Icon & Header -->
                <div class="flex items-center gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-500 flex-shrink-0">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 9V14M12 17.01L12.01 16.9989M12 3L2 21H22L12 3Z" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white/90">Konfirmasi Hapus</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <!-- Body Message -->
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        Apakah Anda yakin ingin menghapus data Academy <strong class="text-gray-800 dark:text-white"
                            x-text="academyName"></strong>? Semua data terkait dengan akademi ini akan dihapus secara
                        permanen.
                    </p>
                </div>

                <!-- Footer Buttons -->
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:bg-transparent dark:text-gray-400 dark:hover:bg-white/5">
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
