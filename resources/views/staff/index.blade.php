@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Staff List') }}</h3>
                <p class="card-description">{{ __('Manajemen data staff academy (coach, admin, finance, dsb).') }}</p>
            </div>

            @can('staff.create')
                <div class="card-actions">
                    <a href="{{ route('staff.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Staff') }}
                    </a>
                </div>
            @endcan
        </div>

        @php
            $allStaffCount = array_sum($statusCounts);
            $hasActiveStaffFilters = !empty($filters);
        @endphp

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <x-table.tabs route="staff.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $allStaffCount],
                'active' => ['label' => __('Aktif'), 'count' => $statusCounts['active']],
                'inactive' => ['label' => __('Nonaktif'), 'count' => $statusCounts['inactive']],
            ]" />
        </div>

        <x-table.toolbar route="staff.index" :filters="$filters" :placeholder="__('Cari nama, nickname, atau kode staff...')">
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

            <div class="form-group">
                <label class="form-label">{{ __('Employment Type') }}</label>
                <select name="id_employment_type" class="form-select">
                    <option value="">{{ __('Semua Employment Type') }}</option>
                    @foreach ($employmentTypeOptions as $type)
                        <option value="{{ $type->id_employment_type }}" @selected(($filters['id_employment_type'] ?? '') === $type->id_employment_type)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Staff Position') }}</label>
                <select name="id_staff_position" class="form-select">
                    <option value="">{{ __('Semua Staff Position') }}</option>
                    @foreach ($staffPositionOptions as $position)
                        <option value="{{ $position->id_staff_position }}" @selected(($filters['id_staff_position'] ?? '') === $position->id_staff_position)>
                            {{ $position->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Gender') }}</label>
                <select name="gender" class="form-select">
                    <option value="">{{ __('Semua Gender') }}</option>
                    <option value="male" @selected(($filters['gender'] ?? '') === 'male')>{{ __('Laki-laki') }}</option>
                    <option value="female" @selected(($filters['gender'] ?? '') === 'female')>{{ __('Perempuan') }}</option>
                </select>
            </div>
        </x-table.toolbar>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Staff') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Employment Type') }}</th>
                        <th class="table-header-cell">{{ __('Staff Position') }}</th>
                        <th class="table-header-cell">{{ __('Telepon') }}</th>
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell">{{ __('Akun') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($staff as $item)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <div class="avatar avatar-square shrink-0">
                                        @if ($item->photo)
                                            <img src="{{ asset('storage/' . $item->photo) }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="avatar-placeholder">{{ strtoupper(substr($item->full_name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="table-title">{{ $item->full_name }}</span>
                                        <span class="table-subtitle">{{ $item->staff_code }}</span>
                                    </div>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $item->academy->name ?? '-' }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                <span class="table-text">{{ $item->activeContract?->employmentType?->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $item->activeContract?->position?->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $item->phone }}</span>
                            </td>

                            <td class="table-cell">
                                @if ($item->activeContract)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell">
                                @if ($item->id_user)
                                    <span class="badge badge-success">{{ __('Ada') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('Belum Ada') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('staff.view')
                                        <a href="{{ route('staff.show', $item) }}" class="btn-icon" title="{{ __('Detail') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M2 10C2 10 5 4 10 4C15 4 18 10 18 10C18 10 15 16 10 16C5 16 2 10 2 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                                <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('staff.update')
                                        <a href="{{ route('staff.edit', $item) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('staff.delete')
                                        <x-button.delete :action="route('staff.destroy', $item)" :name="$item->full_name" />
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
                                    @if ($hasActiveStaffFilters)
                                        <h4 class="empty-title">{{ __('Tidak ada staff yang cocok') }}</h4>
                                        <p class="empty-description">{{ __('Coba ubah kata kunci atau filter yang dipakai') }}</p>
                                        <a href="{{ route('staff.index') }}" class="empty-link">{{ __('Reset Filter') }}</a>
                                    @else
                                        <h4 class="empty-title">{{ __('Belum ada Staff') }}</h4>
                                        <p class="empty-description">{{ __('Tambahkan staff pertama.') }}</p>

                                        @can('staff.create')
                                            <a href="{{ route('staff.create') }}" class="empty-link">{{ __('Tambah Staff') }}</a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($staff as $item)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="avatar avatar-square shrink-0">
                                @if ($item->photo)
                                    <img src="{{ asset('storage/' . $item->photo) }}" class="h-full w-full object-cover">
                                @else
                                    <span class="avatar-placeholder">{{ strtoupper(substr($item->full_name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <span class="table-title truncate">{{ $item->full_name }}</span>
                                <span class="table-subtitle">{{ $item->staff_code }}</span>
                            </div>
                        </div>

                        @if ($item->activeContract)
                            <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge badge-danger shrink-0">{{ __('Nonaktif') }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        @if ($isSuperAdmin)
                            <div class="table-card-field">
                                <span class="table-card-label">{{ __('Academy') }}</span>
                                <span class="badge badge-secondary w-fit">{{ $item->academy->name ?? '-' }}</span>
                            </div>
                        @endif
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Employment Type') }}</span>
                            <span class="table-text">{{ $item->activeContract?->employmentType?->name ?? '-' }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Staff Position') }}</span>
                            <span class="table-text">{{ $item->activeContract?->position?->name ?? '-' }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Telepon') }}</span>
                            <span class="table-text">{{ $item->phone }}</span>
                        </div>
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Akun') }}</span>
                            @if ($item->id_user)
                                <span class="badge badge-success w-fit">{{ __('Ada') }}</span>
                            @else
                                <span class="badge badge-secondary w-fit">{{ __('Belum Ada') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('staff.view')
                            <a href="{{ route('staff.show', $item) }}" class="btn-icon" title="{{ __('Detail') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2 10C2 10 5 4 10 4C15 4 18 10 18 10C18 10 15 16 10 16C5 16 2 10 2 10Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('staff.update')
                            <a href="{{ route('staff.edit', $item) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('staff.delete')
                            <x-button.delete :action="route('staff.destroy', $item)" :name="$item->full_name" />
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
                        @if ($hasActiveStaffFilters)
                            <h4 class="empty-title">{{ __('Tidak ada staff yang cocok') }}</h4>
                            <p class="empty-description">{{ __('Coba ubah kata kunci atau filter yang dipakai') }}</p>
                            <a href="{{ route('staff.index') }}" class="empty-link">{{ __('Reset Filter') }}</a>
                        @else
                            <h4 class="empty-title">{{ __('Belum ada Staff') }}</h4>
                            <p class="empty-description">{{ __('Tambahkan staff pertama.') }}</p>

                            @can('staff.create')
                                <a href="{{ route('staff.create') }}" class="empty-link">{{ __('Tambah Staff') }}</a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if ($staff->hasPages())
            <div class="table-footer">
                {{ $staff->withQueryString()->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
