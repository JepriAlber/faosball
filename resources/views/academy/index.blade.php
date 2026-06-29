@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <!-- Breadcrumb Start -->
    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <!-- Breadcrumb End -->

    <!-- Alerts -->
    <x-alert />
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
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                                    <x-button.delete :action="route('academy.destroy', $academy->id_academy)" :name="$academy->name" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="text-gray-300 dark:text-gray-700 mb-3">
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

    {{-- Delete Confirmation Modal --}}
    <x-modal.delete />
@endsection
