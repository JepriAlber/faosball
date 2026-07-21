@extends('layouts.app', ['page' => 'role'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">{{ __('Informasi Role') }}</h3>
                <p class="card-description">
                    {{ __('Perbarui informasi role dan hak akses yang dimiliki.') }}
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>

        </div>

        <form action="{{ route('roles.update', $role) }}" method="POST" x-data="rolePermissionForm">

            @csrf
            @method('PUT')

            @if ($isSuperAdmin)
                <div class="form-group">
                    <label class="form-label">{{ __('Academy') }}</label>
                    <p class="form-input bg-gray-50 dark:bg-gray-800">
                        {{ $role->id_academy ? $role->academy->name : __('Role System') }}
                    </p>
                </div>
            @endif

            <div class="form-group">

                <label class="form-label">
                    {{ __('Nama Role') }}
                    <span class="text-error-500">*</span>
                </label>

                <input type="text" name="name" value="{{ old('name', $role->name) }}"
                    placeholder="{{ __('Contoh : Finance Manager') }}"
                    class="form-input @error('name') form-danger @elseif(old()) form-success @enderror"
                    required>

                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>

            <div class="mt-8">

                <div class="mb-4">

                    <h4 class="card-title text-base">
                        {{ __('Hak Akses') }}
                    </h4>

                    <p class="card-description">
                        {{ __('Perbarui permission yang dimiliki oleh role ini.') }}
                    </p>

                </div>

                <div
                    class="mb-6 flex flex-col gap-4 rounded-xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-800 dark:bg-brand-950 md:flex-row md:items-center md:justify-between">

                    <div>

                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ __('Permission') }}
                        </h4>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Pilih seluruh permission atau atur setiap modul secara terpisah.') }}
                        </p>

                    </div>

                    <div class="flex flex-wrap gap-3">

                        <button type="button" class="btn btn-primary" @click="selectAll()">
                            {{ __('Pilih Semua') }}
                        </button>

                        <button type="button" class="btn btn-secondary" @click="unselectAll()">
                            {{ __('Hapus Semua') }}
                        </button>

                    </div>

                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

                    @foreach ($permissionGroups as $module => $permissions)
                        <x-forms.permission-group :module="$module" :permissions="$permissions" :selected="old('permissions', $selectedPermissions)" />
                    @endforeach

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Role') }}
                </button>

            </div>

        </form>

    </div>

@endsection
