@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    @php
        $teamTypeLabels = [
            'regular' => __('Regular'),
            'tournament' => __('Tournament'),
            'event' => __('Event'),
            'temporary' => __('Temporary'),
        ];
    @endphp

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Team List') }}</h3>
                <p class="card-description">{{ __('Manajemen tim (reguler, kompetisi, event, sementara) per academy.') }}</p>
            </div>

            @can('team.create')
                <div class="card-actions">
                    <a href="{{ route('teams.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Team') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <x-table.tabs route="teams.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $statusCounts['active'] + $statusCounts['inactive']],
                'active' => ['label' => __('Aktif'), 'count' => $statusCounts['active']],
                'inactive' => ['label' => __('Nonaktif'), 'count' => $statusCounts['inactive']],
            ]" />
        </div>

        <x-table.toolbar route="teams.index" :filters="$filters" placeholder="{{ __('Cari nama atau kode tim...') }}">

            <div class="form-group">
                <label class="form-label">{{ __('Urutkan') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('Terbaru') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Terlama') }}</option>
                    <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>{{ __('Nama A-Z') }}</option>
                    <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>{{ __('Nama Z-A') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Season') }}</label>
                <select name="id_season" class="form-select">
                    <option value="">{{ __('Semua Season') }}</option>
                    @foreach ($seasons as $season)
                        <option value="{{ $season->id_season }}" @selected(($filters['id_season'] ?? '') === $season->id_season)>
                            {{ $season->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Player Category') }}</label>
                <select name="id_player_category" class="form-select">
                    <option value="">{{ __('Semua Kategori') }}</option>
                    @foreach ($playerCategories as $category)
                        <option value="{{ $category->id_player_category }}" @selected(($filters['id_player_category'] ?? '') === $category->id_player_category)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($isSuperAdmin)
                <div class="form-group">
                    <label class="form-label">{{ __('Academy') }}</label>
                    <select name="id_academy" class="form-select">
                        <option value="">{{ __('Semua Academy') }}</option>
                        @foreach ($academies as $academy)
                            <option value="{{ $academy->id_academy }}" @selected(($filters['id_academy'] ?? '') === $academy->id_academy)>
                                {{ $academy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </x-table.toolbar>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Team') }}</th>
                        <th class="table-header-cell">{{ __('Season') }}</th>
                        <th class="table-header-cell">{{ __('Player Category') }}</th>
                        <th class="table-header-cell">{{ __('Team Type') }}</th>
                        <th class="table-header-cell">{{ __('Player/Staff') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($teams as $team)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $team->name }}</span>
                                    <span class="table-subtitle">{{ $team->code }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $team->season->name }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="badge badge-secondary">{{ $team->playerCategory->name }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $teamTypeLabels[$team->team_type] ?? $team->team_type }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $team->active_team_players_count }} / {{ $team->active_team_staff_count }}</span>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $team->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($team->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    <a href="{{ route('teams.show', $team) }}" class="btn-icon" title="{{ __('Lihat') }}">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                            <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                                        </svg>
                                    </a>

                                    @can('team.update')
                                        <a href="{{ route('teams.edit', $team) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('team.delete')
                                        <x-button.delete :action="route('teams.destroy', $team)"
                                            :name="$team->name" :disabled="$team->active_team_players_count > 0 || $team->active_team_staff_count > 0"
                                            reason="{{ __('Tim ini masih memiliki player/staff aktif, tidak dapat dihapus.') }}" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Team') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan team pertama.') }}</p>

                                    @can('team.create')
                                        <a href="{{ route('teams.create') }}" class="empty-link">{{ __('Tambah Team') }}</a>
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
            @forelse ($teams as $team)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $team->name }}</span>
                            <span class="table-subtitle">{{ $team->code }}</span>
                        </div>

                        @if ($team->status)
                            <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge badge-danger shrink-0">{{ __('Nonaktif') }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Season') }}</span>
                            <span class="table-text">{{ $team->season->name }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Player Category') }}</span>
                            <span class="badge badge-secondary w-fit">{{ $team->playerCategory->name }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Team Type') }}</span>
                            <span class="table-text">{{ $teamTypeLabels[$team->team_type] ?? $team->team_type }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Player/Staff') }}</span>
                            <span class="table-text">{{ $team->active_team_players_count }} / {{ $team->active_team_staff_count }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <div class="table-card-field">
                                <span class="table-card-label">{{ __('Academy') }}</span>
                                <span class="badge badge-secondary w-fit">{{ $team->academy->name }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="table-card-actions">
                        <a href="{{ route('teams.show', $team) }}" class="btn-icon" title="{{ __('Lihat') }}">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </a>

                        @can('team.update')
                            <a href="{{ route('teams.edit', $team) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('team.delete')
                            <x-button.delete :action="route('teams.destroy', $team)"
                                :name="$team->name" :disabled="$team->active_team_players_count > 0 || $team->active_team_staff_count > 0"
                                reason="{{ __('Tim ini masih memiliki player/staff aktif, tidak dapat dihapus.') }}" />
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
                        <h4 class="empty-title">{{ __('Belum ada Team') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan team pertama.') }}</p>

                        @can('team.create')
                            <a href="{{ route('teams.create') }}" class="empty-link">{{ __('Tambah Team') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($teams->hasPages())
            <div class="table-footer">
                {{ $teams->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
