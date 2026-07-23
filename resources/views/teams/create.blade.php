@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Team') }}</h3>
                <p class="card-description">{{ __('Tambahkan tim baru untuk academy.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('teams.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('teams.store') }}" method="POST">
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
                            {{ __('Nama Team') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="{{ __('Contoh: U15 A') }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Season') }} <span class="text-error-500">*</span>
                            </label>

                            <select name="id_season" class="form-select @error('id_season') form-danger @enderror" required>
                                <option value="">{{ __('Pilih Season') }}</option>
                                @foreach ($seasons as $season)
                                    <option value="{{ $season->id_season }}" @selected(old('id_season') === $season->id_season)>
                                        {{ $season->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('id_season')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Player Category') }} <span class="text-error-500">*</span>
                            </label>

                            <select name="id_player_category" class="form-select @error('id_player_category') form-danger @enderror" required>
                                <option value="">{{ __('Pilih Player Category') }}</option>
                                @foreach ($playerCategories as $category)
                                    <option value="{{ $category->id_player_category }}" @selected(old('id_player_category') === $category->id_player_category)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('id_player_category')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                </div>

                <div>

                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Team Type') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="team_type" class="form-select @error('team_type') form-danger @enderror" required>
                            <option value="regular" @selected(old('team_type', 'regular') === 'regular')>{{ __('Regular') }}</option>
                            <option value="tournament" @selected(old('team_type') === 'tournament')>{{ __('Tournament') }}</option>
                            <option value="event" @selected(old('team_type') === 'event')>{{ __('Event') }}</option>
                            <option value="temporary" @selected(old('team_type') === 'temporary')>{{ __('Temporary') }}</option>
                        </select>

                        @error('team_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea name="description" rows="3" placeholder="{{ __('Keterangan singkat tentang tim ini') }}"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

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
                    {{ __('Simpan Team') }}
                </button>

            </div>

        </form>

    </div>

@endsection
