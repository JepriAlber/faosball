@extends('layouts.app', ['page' => 'staff-positions'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Staff Position List') }}</h3>
                <p class="card-description">{{ __('Manajemen jabatan staff (Head Coach, Finance Manager, dsb) per academy.') }}</p>
            </div>

            @can('staff_position.create')
                <div class="card-actions">
                    <a href="{{ route('staff-positions.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Staff Position') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Staff Position') }}</th>
                        <th class="table-header-cell">{{ __('Kode') }}</th>
                        <th class="table-header-cell">{{ __('Default Role') }}</th>
                        <th class="table-header-cell">{{ __('Pelatih') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($staffPositions as $staffPosition)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $staffPosition->name }}</span>
                                    <span class="table-subtitle">{{ $staffPosition->description ?? '-' }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                {{ $staffPosition->code }}
                            </td>

                            <td class="table-cell">
                                {{ $staffPosition->role->name ?? '-' }}
                            </td>

                            <td class="table-cell">
                                @if ($staffPosition->is_coach)
                                    <span class="badge badge-success">{{ __('Ya') }}</span>
                                @else
                                    <span>-</span>
                                @endif
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $staffPosition->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($staffPosition->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('staff_position.update')
                                        <a href="{{ route('staff-positions.edit', $staffPosition) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('staff_position.delete')
                                        <x-button.delete :action="route('staff-positions.destroy', $staffPosition)"
                                            :name="$staffPosition->name" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Staff Position') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan staff position pertama.') }}</p>

                                    @can('staff_position.create')
                                        <a href="{{ route('staff-positions.create') }}" class="empty-link">{{ __('Tambah Staff Position') }}</a>
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
            @forelse ($staffPositions as $staffPosition)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $staffPosition->name }}</span>
                            <span class="table-subtitle">{{ $staffPosition->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $staffPosition->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Kode') }}</span>
                            <span>{{ $staffPosition->code }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Default Role') }}</span>
                            <span>{{ $staffPosition->role->name ?? '-' }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Pelatih') }}</span>
                            @if ($staffPosition->is_coach)
                                <span class="badge badge-success w-fit">{{ __('Ya') }}</span>
                            @else
                                <span>-</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($staffPosition->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('staff_position.update')
                            <a href="{{ route('staff-positions.edit', $staffPosition) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('staff_position.delete')
                            <x-button.delete :action="route('staff-positions.destroy', $staffPosition)"
                                :name="$staffPosition->name" />
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
                        <h4 class="empty-title">{{ __('Belum ada Staff Position') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan staff position pertama.') }}</p>

                        @can('staff_position.create')
                            <a href="{{ route('staff-positions.create') }}" class="empty-link">{{ __('Tambah Staff Position') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($staffPositions->hasPages())
            <div class="table-footer">
                {{ $staffPositions->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
