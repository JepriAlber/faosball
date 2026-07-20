@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Profil Player') }}</h3>
                <p class="card-description">{{ __('Perbarui detail informasi player.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                @if ($player->user)
                    @can('user.update')
                        <x-account.dropdown :model="$player" :user="$player->user" route-create="players.account.create"
                            route-edit="players.account.edit" route-password="players.account.password"
                            route-status="players.account.status" />
                    @endcan
                @else
                    @can('user.create')
                        <x-account.dropdown :model="$player" :user="$player->user" route-create="players.account.create"
                            route-edit="players.account.edit" route-password="players.account.password"
                            route-status="players.account.status" />
                    @endcan
                @endif
            </div>
        </div>

        <form action="{{ route('players.update', $player->id_player) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-row">

                {{-- LEFT COLUMN: Scope, Identitas, Klasifikasi/Relasi Wajib --}}
                <div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Player Code') }}</label>

                        <input type="text" value="{{ $player->player_code }}" class="form-input form-disabled" readonly>
                    </div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">{{ __('Academy') }}</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">
                                {{ $player->academy->name ?? '-' }}
                            </p>
                        </div>
                    @endif

                    {{-- Nama --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Player') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name', $player->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nickname --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>

                        <input type="text" name="nick_name" value="{{ old('nick_name', $player->nick_name) }}"
                            class="form-input @error('nick_name') form-danger @enderror">

                        @error('nick_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Type Player --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Type Player') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_type"
                            class="form-select @error('id_player_type') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Type Player') }}</option>
                            @foreach ($playerTypes as $type)
                                <option value="{{ $type->id_player_type }}" @selected(old('id_player_type', $player->id_player_type) === $type->id_player_type)>
                                    {{ $type->name }}@unless ($type->status) ({{ __('nonaktif') }})@endunless
                                </option>
                            @endforeach
                        </select>

                        @error('id_player_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kategori Umur --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Kategori Umur') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_category"
                            class="form-select @error('id_player_category') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Kategori Umur') }}</option>
                            @foreach ($playerCategories as $category)
                                <option value="{{ $category->id_player_category }}" @selected(old('id_player_category', $player->id_player_category) === $category->id_player_category)>
                                    {{ $category->name }} ({{ $category->min_age }}-{{ $category->max_age }}
                                    th)@unless ($category->status) — {{ __('nonaktif') }} @endunless
                                </option>
                            @endforeach
                        </select>

                        @if ($suggestedCategory && $suggestedCategory->id_player_category !== $player->id_player_category)
                            <p class="form-helper">
                                {{ __('Saran berdasarkan umur pemain:') }} <strong>{{ $suggestedCategory->name }}</strong>
                            </p>
                        @endif

                        @error('id_player_category')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Posisi Utama --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Posisi Utama') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_primary_position"
                            class="form-select @error('id_primary_position') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Posisi Utama') }}</option>
                            @foreach ($playerPositions->groupBy('position_group') as $group => $positions)
                                <optgroup label="{{ $group }}">
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id_player_position }}" @selected(old('id_primary_position', $player->id_primary_position) === $position->id_player_position)>
                                            {{ $position->code }} — {{ $position->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        @error('id_primary_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Posisi Kedua --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Posisi Kedua') }}</label>

                        <select name="id_secondary_position"
                            class="form-select @error('id_secondary_position') form-danger @enderror">
                            <option value="">{{ __('Tidak ada') }}</option>
                            @foreach ($playerPositions->groupBy('position_group') as $group => $positions)
                                <optgroup label="{{ $group }}">
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id_player_position }}" @selected(old('id_secondary_position', $player->id_secondary_position) === $position->id_player_position)>
                                            {{ $position->code }} — {{ $position->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <p class="form-helper">{{ __('Opsional. Tidak boleh sama dengan posisi utama.') }}</p>

                        @error('id_secondary_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                {{-- RIGHT COLUMN: Deskriptif, Media, Status --}}
                <div>

                    {{-- Tanggal Lahir --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Tanggal Lahir') }}</label>

                        <input type="date" name="birth_date"
                            value="{{ old('birth_date', $player->birth_date?->format('Y-m-d')) }}"
                            class="form-input @error('birth_date') form-danger @enderror">

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Gender') }}</label>

                        <select name="gender" class="form-select">
                            <option value="">{{ __('Pilih Gender') }}</option>
                            <option value="male" @selected(old('gender', $player->gender) == 'male')>Male</option>
                            <option value="female" @selected(old('gender', $player->gender) == 'female')>Female</option>
                        </select>
                    </div>

                    {{-- Nationality --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Nationality') }}</label>

                        <input type="text" name="nationality" value="{{ old('nationality', $player->nationality) }}"
                            class="form-input">
                    </div>

                    {{-- Tinggi & Berat --}}
                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">{{ __('Tinggi (cm)') }}</label>

                            <input type="number" name="height" value="{{ old('height', $player->height) }}"
                                class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Berat (kg)') }}</label>

                            <input type="number" name="weight" value="{{ old('weight', $player->weight) }}"
                                class="form-input">
                        </div>

                    </div>

                    {{-- Kaki Dominan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Kaki Dominan') }}</label>

                        <select name="preferred_foot" class="form-select">
                            <option value="">{{ __('Pilih') }}</option>
                            <option value="right" @selected(old('preferred_foot', $player->preferred_foot) == 'right')>Right</option>
                            <option value="left" @selected(old('preferred_foot', $player->preferred_foot) == 'left')>Left</option>
                            <option value="both" @selected(old('preferred_foot', $player->preferred_foot) == 'both')>Both</option>
                        </select>
                    </div>

                    {{-- Foto --}}
                    <x-player-photo-field :current-photo-url="$player->photo ? asset('storage/' . $player->photo) : null" />

                    {{-- Catatan --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Catatan') }}
                        </label>

                        <textarea name="notes" rows="4" placeholder="{{ __('Tambahkan catatan player') }}" class="form-textarea">{{ old('notes', $player->notes) }}</textarea>

                        @error('notes')
                            <span class="form-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Status Player') }}</label>

                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $player->status) == 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $player->status) == 'inactive')>Inactive</option>
                            <option value="graduated" @selected(old('status', $player->status) == 'graduated')>Graduated</option>
                            <option value="left" @selected(old('status', $player->status) == 'left')>Left</option>
                        </select>
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Player') }}
                </button>

            </div>

        </form>

    </div>

    <x-modal.reset-password />
    <x-modal.status />

@endsection
