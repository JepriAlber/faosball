@extends('layouts.app', ['page' => 'role'])

@section('title', $role->name . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">

            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    <span class="avatar-placeholder">
                        {{ strtoupper(substr($role->name, 0, 2)) }}
                    </span>
                </div>

                <div>
                    <h3 class="card-title text-xl">{{ $role->name }}</h3>
                    <p class="card-description">
                        {{ $role->permissions->count() }} {{ __('Permission') }} &middot; {{ $role->users->count() }} {{ __('User') }}
                    </p>
                </div>
            </div>

            <div class="card-actions">

                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                @if ($role->name !== config('faos.super_admin_role'))
                    @can('role.update')
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            {{ __('Edit Role') }}
                        </a>
                    @endcan
                @endif

            </div>

        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="min-w-0 space-y-6 lg:col-span-2">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">{{ __('Hak Akses') }}</h4>
                    <p class="section-description">
                        {{ __('Daftar permission yang dimiliki role ini, dikelompokkan per module.') }}
                    </p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">

                        @forelse ($permissionGroups as $module => $permissions)
                            <div x-data="{ open: true }"
                                class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">

                                <button type="button" @click="open=!open"
                                    class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">

                                    <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200"
                                        :class="{ 'rotate-90': open }" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>

                                    <h5 class="text-sm font-semibold text-gray-800 dark:text-white">
                                        {{ \Illuminate\Support\Str::headline($module) }}
                                    </h5>

                                </button>

                                <div x-show="open" class="grid gap-2 p-4">

                                    @foreach ($permissions as $permission)
                                        <div
                                            class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800">

                                            <span class="badge {{ $permission['badge'] }} shrink-0">
                                                {{ $permission['action'] }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                                    {{ $permission['label'] }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $permission['description'] }}
                                                </p>
                                            </div>

                                        </div>
                                    @endforeach

                                </div>

                            </div>
                        @empty
                            <div
                                class="rounded-lg border border-dashed border-gray-200 p-5 dark:border-gray-800 sm:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Role ini belum memiliki permission.') }}
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
                                {{ $role->guard_name }}
                            </span>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Total Permission') }}
                            </span>
                            <span class="table-text">
                                {{ $role->permissions->count() }}
                            </span>
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Total User') }}
                            </span>
                            <span class="table-text">
                                {{ $role->users->count() }}
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
                                {{ $role->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>{{ __('Pembaruan terakhir') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $role->updated_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
