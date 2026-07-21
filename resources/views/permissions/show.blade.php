@extends('layouts.app', ['page' => 'permission'])

@section('title', $permission->name . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">

            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    <span class="avatar-placeholder">
                        {{ strtoupper(substr($permission->name, 0, 2)) }}
                    </span>
                </div>

                <div>
                    <h3 class="card-title text-xl">{{ $presenter['label'] }}</h3>
                    <p class="card-description">
                        <code class="font-mono">{{ $permission->name }}</code>
                        &middot; {{ __(':count Role menggunakan permission ini', ['count' => $permission->roles_count]) }}
                    </p>
                </div>
            </div>

            <div class="card-actions">
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>

        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="min-w-0 space-y-6 lg:col-span-2">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">{{ __('Role Pengguna') }}</h4>
                    <p class="section-description">
                        {{ __('Daftar role yang saat ini memiliki permission ini.') }}
                    </p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">

                        @forelse ($roles as $role)
                            <a href="{{ route('roles.show', $role) }}"
                                class="flex items-center justify-between rounded-lg border border-gray-200 p-4 transition hover:border-brand-300 dark:border-gray-800 dark:hover:border-brand-500">
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $role->name }}
                                </span>
                                <span class="badge badge-secondary">
                                    {{ $role->guard_name }}
                                </span>
                            </a>
                        @empty
                            <div
                                class="rounded-lg border border-dashed border-gray-200 p-5 dark:border-gray-800 sm:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Permission ini belum digunakan oleh role manapun.') }}
                                </p>
                            </div>
                        @endforelse

                    </div>

                </div>

            </div>

            <div class="space-y-6">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Ringkas') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Guard') }}
                            </span>
                            <span class="badge badge-secondary">
                                {{ $permission->guard_name }}
                            </span>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Module') }}
                            </span>
                            <span class="badge badge-secondary">
                                {{ \Illuminate\Support\Str::headline(\App\Support\PermissionPresenter::module($permission->name)) }}
                            </span>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Action') }}
                            </span>
                            <span class="badge {{ $presenter['badge'] }}">
                                {{ $presenter['action'] }}
                            </span>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Total Role') }}
                            </span>
                            <span class="table-text">
                                {{ $permission->roles_count }}
                            </span>
                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        {{ __('Informasi Sistem') }}
                    </h4>

                    <div class="mt-4 space-y-3 text-xs text-gray-400">

                        <div class="flex items-center justify-between">
                            <span>{{ __('Dibuat pada') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $permission->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>{{ __('Pembaruan terakhir') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $permission->updated_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
