@extends('layouts.app', ['page' => 'player-categories'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Player Category') }}</h3>
                <p class="card-description">{{ __('Tambahkan kelompok umur baru untuk academy.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('player-categories.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('player-categories.store') }}" method="POST">
            @csrf

            <div class="form-row">

                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Academy') }} <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror"
                                required>
                                <option value="">{{ __('Pilih Academy') }}</option>
                                @foreach ($academies as $academy)
                                    <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
                                        {{ $academy->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('id_academy')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Kategori') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="{{ __('Contoh: U-12, U-15, U-17') }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea name="description" rows="3" placeholder="{{ __('Keterangan singkat tentang kategori ini') }}"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

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

                            <input type="number" name="min_age" value="{{ old('min_age') }}" min="0" max="99"
                                class="form-input @error('min_age') form-danger @enderror" required>

                            @error('min_age')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Umur Maksimal') }} <span class="text-error-500">*</span>
                            </label>

                            <input type="number" name="max_age" value="{{ old('max_age') }}" min="0" max="99"
                                class="form-input @error('max_age') form-danger @enderror" required>

                            @error('max_age')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <p class="form-helper">
                        {{ __('Rentang ini dipakai untuk') }} <strong>{{ __('menyarankan') }}</strong>
                        {{ __('kategori saat menambah player, berdasarkan tanggal lahirnya. Pemain tetap boleh ditempatkan di kategori yang umurnya di luar rentang ini.') }}
                    </p>

                    <div class="form-group" x-data="{ isActive: {{ old('status', 1) ? 'true' : 'false' }} }">

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

                <button type="reset" class="btn btn-secondary">
                    {{ __('Reset') }}
                </button>

                <button type="submit" class="btn btn-primary">
                    {{ __('Simpan Player Category') }}
                </button>

            </div>

        </form>

    </div>

@endsection
