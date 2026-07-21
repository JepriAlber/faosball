@extends('layouts.app', ['page' => 'permission'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Permission List') }}</h3>
                <p class="card-description">{{ __('Daftar hak akses (permission) yang tersedia pada sistem.') }}</p>
            </div>

            @can('permission.create')
                <div class="card-actions">
                    <a href="{{ route('permissions.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Permission') }}
                    </a>
                </div>
            @endcan
        </div>

        <x-table.toolbar route="permissions.index" :filters="$filters" placeholder="{{ __('Cari nama permission...') }}">

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
                <label class="form-label">{{ __('Module') }}</label>
                <select name="module" class="form-select">
                    <option value="">{{ __('Semua Module') }}</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>
                            {{ \App\Support\PermissionPresenter::moduleLabel($module) }}
                        </option>
                    @endforeach
                </select>
            </div>

        </x-table.toolbar>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Permission') }}</th>
                        <th class="table-header-cell">{{ __('Module') }}</th>
                        <th class="table-header-cell">{{ __('Action') }}</th>
                        <th class="table-header-cell">{{ __('Guard') }}</th>
                        <th class="table-header-cell">{{ __('Role') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse($permissions as $permission)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <a href="{{ route('permissions.show', $permission) }}"
                                        class="table-title">{{ $permission->name }}</a>
                                    <span class="table-subtitle">{{ $permission->created_at->format('d M Y') }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="badge badge-secondary">
                                    {{ \Illuminate\Support\Str::headline(\App\Support\PermissionPresenter::module($permission->name)) }}
                                </span>
                            </td>

                            <td class="table-cell">
                                <span class="badge {{ \App\Support\PermissionPresenter::badge($permission->name) }}">
                                    {{ \App\Support\PermissionPresenter::actionLabel($permission->name) }}
                                </span>
                            </td>

                            <td class="table-cell">
                                <span class="badge badge-secondary">{{ $permission->guard_name }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $permission->roles_count }} {{ __('Role') }}</span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('permission.view')
                                        <a href="{{ route('permissions.show', $permission) }}"
                                            class="btn-icon btn-icon-primary" title="{{ __('Detail') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path
                                                    d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                                <path
                                                    d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('permission.delete')
                                        <x-button.delete :action="route('permissions.destroy', $permission)"
                                            :name="$permission->name" :disabled="$permission->roles_count > 0"
                                            reason="{{ __('Permission masih digunakan oleh role, tidak dapat dihapus.') }}" />
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada Permission') }}</h4>
                                    <p class="empty-description">{{ __('Tambahkan permission pertama.') }}</p>

                                    @can('permission.create')
                                        <a href="{{ route('permissions.create') }}" class="empty-link">{{ __('Tambah Permission') }}</a>
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
            @forelse($permissions as $permission)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <a href="{{ route('permissions.show', $permission) }}"
                                class="table-title truncate">{{ $permission->name }}</a>
                            <span class="table-subtitle">{{ $permission->created_at->format('d M Y') }}</span>
                        </div>

                        <span class="badge {{ \App\Support\PermissionPresenter::badge($permission->name) }} shrink-0">
                            {{ \App\Support\PermissionPresenter::actionLabel($permission->name) }}
                        </span>
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Module') }}</span>
                            <span class="badge badge-secondary w-fit">
                                {{ \Illuminate\Support\Str::headline(\App\Support\PermissionPresenter::module($permission->name)) }}
                            </span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Guard') }}</span>
                            <span class="badge badge-secondary w-fit">{{ $permission->guard_name }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Role') }}</span>
                            <span class="table-text">{{ $permission->roles_count }} {{ __('Role') }}</span>
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('permission.view')
                            <a href="{{ route('permissions.show', $permission) }}"
                                class="btn-icon btn-icon-primary" title="{{ __('Detail') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path
                                        d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <path
                                        d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        @can('permission.delete')
                            <x-button.delete :action="route('permissions.destroy', $permission)"
                                :name="$permission->name" :disabled="$permission->roles_count > 0"
                                reason="{{ __('Permission masih digunakan oleh role, tidak dapat dihapus.') }}" />
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
                        <h4 class="empty-title">{{ __('Belum ada Permission') }}</h4>
                        <p class="empty-description">{{ __('Tambahkan permission pertama.') }}</p>

                        @can('permission.create')
                            <a href="{{ route('permissions.create') }}" class="empty-link">{{ __('Tambah Permission') }}</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($permissions->hasPages())
            <div class="table-footer">
                {{ $permissions->withQueryString()->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
