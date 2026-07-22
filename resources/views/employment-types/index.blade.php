@extends('layouts.app', ['page' => 'employment-types'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Employment Type List') }}</h3>
                <p class="card-description">{{ __('Manajemen jenis pekerjaan staff (Permanent, Contract, Intern, dsb) per academy.') }}</p>
            </div>

            @can('employment_type.create')
                <div class="card-actions">
                    <a href="{{ route('employment-types.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Employment Type') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Employment Type') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($employmentTypes as $employmentType)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $employmentType->name }}</span>
                                    <span class="table-subtitle">{{ $employmentType->description ?? '-' }}</span>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $employmentType->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                @if ($employmentType->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('employment_type.update')
                                        <a href="{{ route('employment-types.edit', $employmentType) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('employment_type.delete')
                                        <x-button.delete :action="route('employment-types.destroy', $employmentType)"
                                            :name="$employmentType->name" :disabled="$employmentType->contracts_count > 0"
                                            reason="{{ __('Employment type masih digunakan oleh kontrak staff, tidak dapat dihapus.') }}" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 4 : 3 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Employment Type') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan employment type pertama.') }}</p>

                                    @can('employment_type.create')
                                        <a href="{{ route('employment-types.create') }}" class="empty-link">{{ __('Tambah Employment Type') }}</a>
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
            @forelse ($employmentTypes as $employmentType)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $employmentType->name }}</span>
                            <span class="table-subtitle">{{ $employmentType->description ?? '-' }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <span class="badge badge-secondary shrink-0">{{ $employmentType->academy->name }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Status') }}</span>
                            @if ($employmentType->status)
                                <span class="badge badge-success w-fit">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger w-fit">{{ __('Nonaktif') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('employment_type.update')
                            <a href="{{ route('employment-types.edit', $employmentType) }}" class="btn-icon btn-icon-warning"
                                title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('employment_type.delete')
                            <x-button.delete :action="route('employment-types.destroy', $employmentType)"
                                :name="$employmentType->name" :disabled="$employmentType->contracts_count > 0"
                                reason="{{ __('Employment type masih digunakan oleh kontrak staff, tidak dapat dihapus.') }}" />
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
                        <h4 class="empty-title">{{ __('Belum ada Employment Type') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan employment type pertama.') }}</p>

                        @can('employment_type.create')
                            <a href="{{ route('employment-types.create') }}" class="empty-link">{{ __('Tambah Employment Type') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($employmentTypes->hasPages())
            <div class="table-footer">
                {{ $employmentTypes->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
