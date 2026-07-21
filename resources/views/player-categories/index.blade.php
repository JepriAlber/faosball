@extends('layouts.app', ['page' => 'player-categories'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Player Category List') }}</h3>
                <p class="card-description">{{ __('Manajemen kelompok umur (U-12, U-15, U-17, dsb) per academy.') }}</p>
            </div>

            @can('player_category.create')
                <div class="card-actions">
                    <a href="{{ route('player-categories.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Player Category') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Kategori') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Rentang Umur') }}</th>
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell">{{ __('Player') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($playerCategories as $playerCategory)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $playerCategory->name }}</span>
                                    <span class="table-subtitle">{{ $playerCategory->description ?? '-' }}</span>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $playerCategory->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                <span class="table-text">
                                    {{ __(':min–:max tahun', ['min' => $playerCategory->min_age, 'max' => $playerCategory->max_age]) }}
                                </span>
                            </td>

                            <td class="table-cell">
                                @if ($playerCategory->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $playerCategory->players_count }} {{ __('Player') }}</span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('player_category.update')
                                        <a href="{{ route('player-categories.edit', $playerCategory) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('player_category.delete')
                                        <x-button.delete :action="route('player-categories.destroy', $playerCategory)"
                                            :name="$playerCategory->name" :disabled="$playerCategory->players_count > 0"
                                            reason="{{ __('Kategori masih digunakan oleh player, tidak dapat dihapus.') }}" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Player Category') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan player category pertama.') }}</p>

                                    @can('player_category.create')
                                        <a href="{{ route('player-categories.create') }}" class="empty-link">{{ __('Tambah Player Category') }}</a>
                                    @endcan

                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($playerCategories as $playerCategory)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $playerCategory->name }}</span>
                            <span class="table-subtitle">{{ $playerCategory->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $playerCategory->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Rentang Umur') }}</span>
                            <span class="table-text">{{ __(':min–:max tahun', ['min' => $playerCategory->min_age, 'max' => $playerCategory->max_age]) }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($playerCategory->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Player') }}</span>
                            <span class="table-text">{{ $playerCategory->players_count }} {{ __('Player') }}</span>
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('player_category.update')
                            <a href="{{ route('player-categories.edit', $playerCategory) }}"
                                class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('player_category.delete')
                            <x-button.delete :action="route('player-categories.destroy', $playerCategory)"
                                :name="$playerCategory->name" :disabled="$playerCategory->players_count > 0"
                                reason="{{ __('Kategori masih digunakan oleh player, tidak dapat dihapus.') }}" />
                        @endcan
                    </div>
                </div>
            @empty
                <div class="table-card">
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                            class="text-gray-300 dark:text-gray-700 mb-3">
                            <path
                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                stroke="currentColor" stroke-width="2.5" />
                        </svg>
                        <h4 class="empty-title">{{ __('Belum ada Player Category') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan player category pertama.') }}</p>

                        @can('player_category.create')
                            <a href="{{ route('player-categories.create') }}" class="empty-link">{{ __('Tambah Player Category') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($playerCategories->hasPages())
            <div class="table-footer">
                {{ $playerCategories->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
