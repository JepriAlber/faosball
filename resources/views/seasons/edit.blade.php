@extends('layouts.app', ['page' => 'seasons'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Season') }}</h3>
                <p class="card-description">{{ __('Perbarui detail season.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('seasons.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('seasons.update', $season) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">

                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">{{ __('Academy') }}</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">
                                {{ $season->academy->name }}
                            </p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Season') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name', $season->name) }}"
                            placeholder="{{ __('Contoh: 2026') }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">{{ __('Tanggal Mulai') }}</label>

                            <input type="date" name="start_date"
                                value="{{ old('start_date', $season->start_date?->format('Y-m-d')) }}"
                                class="form-input @error('start_date') form-danger @enderror">

                            @error('start_date')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Tanggal Berakhir') }}</label>

                            <input type="date" name="end_date"
                                value="{{ old('end_date', $season->end_date?->format('Y-m-d')) }}"
                                class="form-input @error('end_date') form-danger @enderror">

                            @error('end_date')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <div class="form-group"
                        x-data="{ isActive: {{ old('status', $season->status) ? 'true' : 'false' }} }">

                        <label class="form-label">{{ __('Status') }}</label>

                        <input type="hidden" name="status" :value="isActive ? 1 : 0">

                        <label class="flex cursor-pointer items-center">

                            <input type="checkbox" class="sr-only" :checked="isActive" @change="isActive = !isActive">

                            <div class="form-toggle" :class="isActive && 'form-toggle-active'">
                                <span class="form-toggle-dot" :class="isActive && 'form-toggle-checked'"></span>
                            </div>

                            <span class="ml-3 text-sm text-gray-500" x-text="isActive ? '{{ __('Aktif') }}' : '{{ __('Nonaktif') }}'">
                            </span>

                        </label>

                        @error('status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a href="{{ route('seasons.index') }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Season') }}
                </button>

            </div>

        </form>

    </div>

@endsection
