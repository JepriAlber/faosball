@extends('layouts.app', ['page' => 'permission'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Permission</h3>
                <p class="card-description">
                    Buat permission baru dengan format <code>module.action</code>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('permissions.store') }}" method="POST"
            x-data="{ module: '{{ addslashes(old('module', '')) }}', action: '{{ addslashes(old('action', '')) }}' }">

            @csrf

            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Module
                        <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="module" list="module-options" x-model="module"
                        placeholder="Contoh: coach"
                        class="form-input @error('module') form-danger @elseif(old('module')) form-success @enderror"
                        required>

                    <datalist id="module-options">
                        @foreach ($modules as $module)
                            <option value="{{ $module }}"></option>
                        @endforeach
                    </datalist>

                    <p class="form-helper">Boleh module yang sudah ada, atau ketik module baru.</p>

                    @error('module')
                        <span class="form-error">{{ $message }}</span>
                    @enderror

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Action
                        <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="action" list="action-options" x-model="action"
                        placeholder="Contoh: view"
                        class="form-input @error('action') form-danger @elseif(old('action')) form-success @enderror"
                        required>

                    <datalist id="action-options">
                        @foreach ($actions as $actionOption)
                            <option value="{{ $actionOption }}"></option>
                        @endforeach
                    </datalist>

                    <p class="form-helper">Pilih action yang sudah dikenal, atau ketik action baru.</p>

                    @error('action')
                        <span class="form-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

            <div class="rounded-xl border border-dashed border-gray-200 p-4 dark:border-gray-800">
                <p class="text-xs text-gray-400">Permission yang akan disimpan</p>
                <p class="mt-1 font-mono text-sm font-semibold text-gray-800 dark:text-white"
                    x-text="(module || '...') + '.' + (action || '...')">
                </p>
            </div>

            @error('name')
                <p class="form-error mt-2">{{ $message }}</p>
            @enderror

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Simpan Permission
                </button>

            </div>

        </form>

    </div>

@endsection
