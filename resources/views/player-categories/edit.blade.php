@extends('layouts.app', ['page' => 'player-categories'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Player Category') }}</h3>
                <p class="card-description">{{ __('Perbarui detail kelompok umur.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('player-categories.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('player-categories.update', $playerCategory) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">

                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">{{ __('Academy') }}</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">
                                {{ $playerCategory->academy->name }}
                            </p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Kategori') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name', $playerCategory->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea name="description" rows="3"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $playerCategory->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Umur Minimal') }} <span class="text-error-500">*</span>
                            </label>

                            <input type="number" name="min_age" value="{{ old('min_age', $playerCategory->min_age) }}"
                                min="0" max="99" class="form-input @error('min_age') form-danger @enderror" required>

                            @error('min_age')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Umur Maksimal') }} <span class="text-error-500">*</span>
                            </label>

                            <input type="number" name="max_age" value="{{ old('max_age', $playerCategory->max_age) }}"
                                min="0" max="99" class="form-input @error('max_age') form-danger @enderror" required>

                            @error('max_age')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <p class="form-helper">
                        {{ __('Rentang ini dipakai untuk') }} <strong>{{ __('menyarankan') }}</strong>
                        {{ __('kategori saat menambah player, berdasarkan tanggal lahirnya. Pemain tetap boleh ditempatkan di kategori yang umurnya di luar rentang ini.') }}
                    </p>

                    <div class="form-group"
                        x-data="{ isActive: {{ old('status', $playerCategory->status) ? 'true' : 'false' }} }">

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

                <a href="{{ route('player-categories.index') }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Player Category') }}
                </button>

            </div>

        </form>

    </div>

@endsection
