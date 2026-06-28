@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <div x-data="{ showDeleteModal: false, deleteAction: '', academyName: '' }">
        <!-- Breadcrumb Start -->
        <div x-data="{ pageName: @js($title) }">
            @include('partials.breadcrumb')
        </div>
        <!-- Breadcrumb End -->

        <!-- Alerts -->
        @include('partials.alert')
        <!-- Alerts End -->

        <div class="card">

            <div class="card-header">
                <div>
                    <h3 class="card-title">Academy List</h3>
                    <p class="card-description">Manajemen profil, tagline, dan status akademi
                        sepak bola.</p>
                </div>
                <div class="card-actions">
                    <a href="{{ route('academy.create') }}" class="btn btn-primary">
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
            <div class="table-wrapper">
                <table class="table">
                    <thead class="table-head">
                        <tr class="table-header-row">
                            <th class="table-header-cell">Info Academy </th>
                            <th class="table-header-cell">Kontak</th>
                            <th class="table-header-cell">Tagline</th>
                            <th class="table-header-cell">Status</th>
                            <th class="table-header-cell text-center"> Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($academies as $academy)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <div class="flex items-center gap-3">
                                        <div class="table-avatar">
                                            @if ($academy->logo)
                                                <img src="{{ asset('storage/' . $academy->logo) }}"
                                                    alt="Logo {{ $academy->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="avatar-placeholder">
                                                    {{ strtoupper(substr($academy->name, 0, 2)) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('academy.show', $academy->id_academy) }}" class="table-title">
                                                {{ $academy->name }}
                                            </a>
                                            <span class="table-subtitle">
                                                {{ $academy->slug }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell">
                                    <span class="table-text">{{ $academy->email }}</span>
                                    <span class="table-subtitle">{{ $academy->phone }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="table-description">"{{ $academy->tagline }}"</span>
                                </td>
                                <td class="table-cell">
                                    @if ($academy->status)
                                        <span class="badge badge-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <div class="table-action">
                                        <a href="{{ route('academy.show', $academy->id_academy) }}"
                                            class="btn-icon btn-icon-primary" title="Detail">
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
                                            class="btn-icon btn-icon-warning" title="Edit">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                        <button type="button"
                                            @click="deleteAction = '{{ route('academy.destroy', $academy->id_academy) }}'; academyName = '{{ addslashes($academy->name) }}'; showDeleteModal = true"
                                            class="btn-icon btn-icon-danger" title="Hapus">
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
                                <td colspan="5" class="table-empty">
                                    <div class="empty-state">
                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-gray-300 dark:text-gray-700 mb-3">
                                            <path
                                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <h4 class="empty-title">Belum ada data Academy</h4>
                                        <p class="empty-description">Tambah academy sekarang</p>
                                        <a href="{{ route('academy.create') }}" class="empty-link">Tambah
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
                <div class="table-footer">
                    {{ $academies->links() }}
                </div>
            @endif
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" class="modal-overlay flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
            <div class="modal-container modal-md" @click.away="showDeleteModal = false"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                <div class="modal-header">
                    <div class="flex items-center gap-4">
                        <span class="modal-icon modal-icon-danger">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 9V14M12 17.01L12.01 16.9989M12 3L2 21H22L12 3Z" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>

                        <div>
                            <h3 class="modal-title">Konfirmasi Hapus</h3>
                            <p class="modal-description">Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-body">
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Apakah Anda yakin ingin menghapus data Academy
                        <strong class="font-semibold text-gray-800 dark:text-white" x-text="academyName"></strong>?
                        Semua data terkait dengan akademi ini akan dihapus secara permanen.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">
                        Batal
                    </button>

                    <form :action="deleteAction" method="POST">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger">
                            Hapus Permanen
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection
