@extends('layouts.app', ['page' => 'player-positions'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Master Posisi Pemain</h3>
                <p class="card-description">Daftar posisi pemain global yang dipakai bersama seluruh academy.</p>
            </div>

            @can('player_position.create')
                <div class="card-actions">
                    <a href="{{ route('player-positions.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Tambah Posisi
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">Kode</th>
                        <th class="table-header-cell">Nama</th>
                        <th class="table-header-cell">Kelompok</th>
                        <th class="table-header-cell">Urutan</th>
                        <th class="table-header-cell">Status</th>
                        <th class="table-header-cell">Dipakai</th>
                        <th class="table-header-cell text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($playerPositions as $playerPosition)

                        <tr class="table-row">

                            <td class="table-cell">
                                <span class="badge badge-primary">{{ $playerPosition->code }}</span>
                            </td>

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $playerPosition->name }}</span>
                                    <span class="table-subtitle">{{ $playerPosition->description ?? '-' }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="badge badge-secondary">{{ $playerPosition->position_group }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $playerPosition->sort_order }}</span>
                            </td>

                            <td class="table-cell">
                                @if ($playerPosition->status)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>

                            <td class="table-cell">
                                <span class="table-text">
                                    {{ $playerPosition->primary_players_count + $playerPosition->secondary_players_count }}
                                    Player
                                </span>
                                <span class="table-subtitle">
                                    {{ $playerPosition->primary_players_count }} utama &middot;
                                    {{ $playerPosition->secondary_players_count }} kedua
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('player_position.update')
                                        <a href="{{ route('player-positions.edit', $playerPosition) }}"
                                            class="btn-icon btn-icon-warning" title="Edit">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('player_position.delete')
                                        <x-button.delete :action="route('player-positions.destroy', $playerPosition)"
                                            :name="$playerPosition->name"
                                            :disabled="$playerPosition->primary_players_count + $playerPosition->secondary_players_count > 0"
                                            reason="Posisi masih digunakan oleh player, tidak dapat dihapus." />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">Belum ada Posisi Pemain</h4>
                                    <p class="empty-description">Tambahkan posisi pemain pertama.</p>

                                    @can('player_position.create')
                                        <a href="{{ route('player-positions.create') }}" class="empty-link">Tambah
                                            Posisi</a>
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
            @forelse ($playerPositions as $playerPosition)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-primary shrink-0">{{ $playerPosition->code }}</span>
                                <span class="table-title truncate">{{ $playerPosition->name }}</span>
                            </div>
                            <span class="table-subtitle">{{ $playerPosition->description ?? '-' }}</span>
                        </div>

                        <span class="badge badge-secondary shrink-0">{{ $playerPosition->position_group }}</span>
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">Urutan</span>
                            <span class="table-text">{{ $playerPosition->sort_order }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">Status</span>
                            @if ($playerPosition->status)
                                <span class="badge badge-success w-fit">Aktif</span>
                            @else
                                <span class="badge badge-danger w-fit">Nonaktif</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">Dipakai</span>
                            <span class="table-text">
                                {{ $playerPosition->primary_players_count + $playerPosition->secondary_players_count }}
                                Player
                            </span>
                            <span class="table-subtitle">
                                {{ $playerPosition->primary_players_count }} utama &middot;
                                {{ $playerPosition->secondary_players_count }} kedua
                            </span>
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('player_position.update')
                            <a href="{{ route('player-positions.edit', $playerPosition) }}"
                                class="btn-icon btn-icon-warning" title="Edit">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('player_position.delete')
                            <x-button.delete :action="route('player-positions.destroy', $playerPosition)"
                                :name="$playerPosition->name"
                                :disabled="$playerPosition->primary_players_count + $playerPosition->secondary_players_count > 0"
                                reason="Posisi masih digunakan oleh player, tidak dapat dihapus." />
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
                        <h4 class="empty-title">Belum ada Posisi Pemain</h4>
                        <p class="empty-description">Tambahkan posisi pemain pertama.</p>

                        @can('player_position.create')
                            <a href="{{ route('player-positions.create') }}" class="empty-link">Tambah Posisi</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($playerPositions->hasPages())
            <div class="table-footer">
                {{ $playerPositions->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
