@extends('layouts.app', ['page' => 'player-types'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Player Type List') }}</h3>
                <p class="card-description">{{ __('Manajemen jenis pemain (Reguler, Beasiswa, Trial, dsb) per academy.') }}</p>
            </div>

            @can('player_type.create')
                <div class="card-actions">
                    <a href="{{ route('player-types.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Player Type') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Type') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Tagihan') }}</th>
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell">{{ __('Player') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($playerTypes as $playerType)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $playerType->name }}</span>
                                    <span class="table-subtitle">{{ $playerType->description ?? '-' }}</span>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $playerType->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($playerType->is_billable)
                                    <span class="badge badge-success">{{ __('Ditagih') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Tidak Ditagih') }}</span>
                                @endif
                            </td>

                            <td class="table-cell">
                                @if ($playerType->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $playerType->players_count }} {{ __('Player') }}</span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('player_type.update')
                                        <a href="{{ route('player-types.edit', $playerType) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('player_type.delete')
                                        <x-button.delete :action="route('player-types.destroy', $playerType)" :name="$playerType->name"
                                            :disabled="$playerType->players_count > 0"
                                            reason="{{ __('Type masih digunakan oleh player, tidak dapat dihapus.') }}" />
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
                                    <h4 class="empty-title">{{ __('Belum ada Player Type') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan player type pertama.') }}</p>

                                    @can('player_type.create')
                                        <a href="{{ route('player-types.create') }}" class="empty-link">{{ __('Tambah Player Type') }}</a>
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
            @forelse ($playerTypes as $playerType)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $playerType->name }}</span>
                            <span class="table-subtitle">{{ $playerType->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $playerType->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Tagihan') }}</span>
                            @if ($playerType->is_billable)
                                <span class="badge badge-success w-fit">{{ __('Ditagih') }}</span>
                            @else
                                <span class="badge badge-secondary w-fit">{{ __('Tidak Ditagih') }}</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($playerType->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Player') }}</span>
                            <span class="table-text">{{ $playerType->players_count }} {{ __('Player') }}</span>
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('player_type.update')
                            <a href="{{ route('player-types.edit', $playerType) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('player_type.delete')
                            <x-button.delete :action="route('player-types.destroy', $playerType)" :name="$playerType->name"
                                :disabled="$playerType->players_count > 0"
                                reason="{{ __('Type masih digunakan oleh player, tidak dapat dihapus.') }}" />
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
                        <h4 class="empty-title">{{ __('Belum ada Player Type') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan player type pertama.') }}</p>

                        @can('player_type.create')
                            <a href="{{ route('player-types.create') }}" class="empty-link">{{ __('Tambah Player Type') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($playerTypes->hasPages())
            <div class="table-footer">
                {{ $playerTypes->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
