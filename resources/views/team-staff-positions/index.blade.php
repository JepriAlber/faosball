@extends('layouts.app', ['page' => 'team-staff-positions'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Team Staff Position List') }}</h3>
                <p class="card-description">{{ __('Manajemen peran staff di tim (Head Coach, Team Manager, dsb) per academy.') }}</p>
            </div>

            @can('team_staff_position.create')
                <div class="card-actions">
                    <a href="{{ route('team-staff-positions.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Team Staff Position') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <x-table.tabs route="team-staff-positions.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $statusCounts['active'] + $statusCounts['inactive']],
                'active' => ['label' => __('Aktif'), 'count' => $statusCounts['active']],
                'inactive' => ['label' => __('Nonaktif'), 'count' => $statusCounts['inactive']],
            ]" />
        </div>

        <x-table.toolbar route="team-staff-positions.index" :filters="$filters" placeholder="{{ __('Cari nama peran...') }}">

            <div class="form-group">
                <label class="form-label">{{ __('Urutkan') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('Terbaru') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Terlama') }}</option>
                    <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>{{ __('Nama A-Z') }}</option>
                    <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>{{ __('Nama Z-A') }}</option>
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
                        <th class="table-header-cell">{{ __('Team Staff Position') }}</th>
                        <th class="table-header-cell">{{ __('Kode') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($teamStaffPositions as $teamStaffPosition)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $teamStaffPosition->name }}</span>
                                    <span class="table-subtitle">{{ $teamStaffPosition->description ?? '-' }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                {{ $teamStaffPosition->code }}
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $teamStaffPosition->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($teamStaffPosition->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('team_staff_position.update')
                                        <a href="{{ route('team-staff-positions.edit', $teamStaffPosition) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('team_staff_position.delete')
                                        <x-button.delete :action="route('team-staff-positions.destroy', $teamStaffPosition)"
                                            :name="$teamStaffPosition->name" :disabled="$teamStaffPosition->team_staff_count > 0"
                                            reason="{{ __('Team staff position masih digunakan oleh staff tim, tidak dapat dihapus.') }}" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 5 : 4 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Team Staff Position') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan team staff position pertama.') }}</p>

                                    @can('team_staff_position.create')
                                        <a href="{{ route('team-staff-positions.create') }}" class="empty-link">{{ __('Tambah Team Staff Position') }}</a>
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
            @forelse ($teamStaffPositions as $teamStaffPosition)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $teamStaffPosition->name }}</span>
                            <span class="table-subtitle">{{ $teamStaffPosition->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $teamStaffPosition->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Kode') }}</span>
                            <span>{{ $teamStaffPosition->code }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($teamStaffPosition->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('team_staff_position.update')
                            <a href="{{ route('team-staff-positions.edit', $teamStaffPosition) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('team_staff_position.delete')
                            <x-button.delete :action="route('team-staff-positions.destroy', $teamStaffPosition)"
                                :name="$teamStaffPosition->name" :disabled="$teamStaffPosition->team_staff_count > 0"
                                reason="{{ __('Team staff position masih digunakan oleh staff tim, tidak dapat dihapus.') }}" />
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
                        <h4 class="empty-title">{{ __('Belum ada Team Staff Position') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan team staff position pertama.') }}</p>

                        @can('team_staff_position.create')
                            <a href="{{ route('team-staff-positions.create') }}" class="empty-link">{{ __('Tambah Team Staff Position') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($teamStaffPositions->hasPages())
            <div class="table-footer">
                {{ $teamStaffPositions->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
