@extends('layouts.app', ['page' => 'role'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Role List</h3>
                <p class="card-description">Manajemen hak akses berdasarkan peran pengguna dalam sistem.</p>
            </div>

            @can('role.create')
                <div class="card-actions">
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Tambah Role
                    </a>
                </div>
            @endcan
        </div>

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">Role</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">Academy</th>
                        @endif
                        <th class="table-header-cell">Permission</th>
                        <th class="table-header-cell">User</th>
                        <th class="table-header-cell text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse($roles as $role)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <a href="{{ route('roles.show', $role) }}" class="table-title">{{ $role->name }}</a>
                                    <span class="table-subtitle">{{ $role->created_at->format('d M Y') }}</span>
                                </div>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    @if ($role->id_academy)
                                        <span class="badge badge-secondary">{{ $role->academy->name }}</span>
                                    @else
                                        <span class="badge badge-primary">Role System</span>
                                    @endif
                                </td>
                            @endif

                            <td class="table-cell">
                                <span class="table-text">{{ $role->permissions_count }} Permission</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $role->users_count }} User</span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">

                                    @can('role.view')
                                        <a href="{{ route('roles.show', $role) }}" class="btn-icon btn-icon-primary"
                                            title="Detail">
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

                                    @can('role.update')
                                        @if ($role->name !== config('faos.super_admin_role'))
                                            <a href="{{ route('roles.edit', $role) }}" class="btn-icon btn-icon-warning"
                                                title="Edit">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                        stroke="currentColor" stroke-width="1.5" />
                                                </svg>
                                            </a>
                                        @endif
                                    @endcan

                                    @can('role.delete')
                                        @if ($role->name !== config('faos.super_admin_role'))
                                            <x-button.delete :action="route('roles.destroy', $role)" :name="$role->name" :disabled="$role->users_count > 0"
                                                reason="Role masih digunakan oleh user, tidak dapat dihapus." />
                                        @endif
                                    @endcan

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">Belum ada Role</h4>
                                    <p class="empty-description">Tambahkan role pertama.</p>

                                    @can('role.create')
                                        <a href="{{ route('roles.create') }}" class="empty-link">Tambah Role</a>
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
            @forelse($roles as $role)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <a href="{{ route('roles.show', $role) }}" class="table-title truncate">{{ $role->name }}</a>
                            <span class="table-subtitle">{{ $role->created_at->format('d M Y') }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            @if ($role->id_academy)
                                <span class="badge badge-secondary shrink-0">{{ $role->academy->name }}</span>
                            @else
                                <span class="badge badge-primary shrink-0">Role System</span>
                            @endif
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">Permission</span>
                            <span class="table-text">{{ $role->permissions_count }} Permission</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">User</span>
                            <span class="table-text">{{ $role->users_count }} User</span>
                        </div>
                    </div>

                    <div class="table-card-actions">
                        @can('role.view')
                            <a href="{{ route('roles.show', $role) }}" class="btn-icon btn-icon-primary"
                                title="Detail">
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

                        @can('role.update')
                            @if ($role->name !== config('faos.super_admin_role'))
                                <a href="{{ route('roles.edit', $role) }}" class="btn-icon btn-icon-warning"
                                    title="Edit">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                </a>
                            @endif
                        @endcan

                        @can('role.delete')
                            @if ($role->name !== config('faos.super_admin_role'))
                                <x-button.delete :action="route('roles.destroy', $role)" :name="$role->name" :disabled="$role->users_count > 0"
                                    reason="Role masih digunakan oleh user, tidak dapat dihapus." />
                            @endif
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
                        <h4 class="empty-title">Belum ada Role</h4>
                        <p class="empty-description">Tambahkan role pertama.</p>

                        @can('role.create')
                            <a href="{{ route('roles.create') }}" class="empty-link">Tambah Role</a>
                        @endcan
                    </div>
                </div>
            @endforelse
        </div>

        @if ($roles->hasPages())
            <div class="table-footer">
                {{ $roles->links() }}
            </div>
        @endif

    </div>

    <x-modal.delete />

@endsection
