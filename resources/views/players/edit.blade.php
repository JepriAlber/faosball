@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Profil Player</h3>
                <p class="card-description">Perbarui detail informasi player.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('players.update', $player->id_player) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-row">

                {{-- LEFT COLUMN --}}
                <div>

                    <div class="form-group">
                        <label class="form-label">Player Code</label>

                        <input type="text" value="{{ $player->player_code }}" class="form-input form-disabled" readonly>
                    </div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">Academy</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">
                                {{ $player->academy->name ?? '-' }}
                            </p>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            Type Player <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_type"
                            class="form-select @error('id_player_type') form-danger @enderror" required>
                            <option value="">Pilih Type Player</option>
                            @foreach ($playerTypes as $type)
                                <option value="{{ $type->id_player_type }}" @selected(old('id_player_type', $player->id_player_type) === $type->id_player_type)>
                                    {{ $type->name }}@unless ($type->status) (nonaktif)@endunless
                                </option>
                            @endforeach
                        </select>

                        @error('id_player_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Kategori Umur <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_category"
                            class="form-select @error('id_player_category') form-danger @enderror" required>
                            <option value="">Pilih Kategori Umur</option>
                            @foreach ($playerCategories as $category)
                                <option value="{{ $category->id_player_category }}" @selected(old('id_player_category', $player->id_player_category) === $category->id_player_category)>
                                    {{ $category->name }} ({{ $category->min_age }}-{{ $category->max_age }}
                                    th)@unless ($category->status) — nonaktif @endunless
                                </option>
                            @endforeach
                        </select>

                        @if ($suggestedCategory && $suggestedCategory->id_player_category !== $player->id_player_category)
                            <p class="form-helper">
                                Saran berdasarkan umur pemain: <strong>{{ $suggestedCategory->name }}</strong>
                            </p>
                        @endif

                        @error('id_player_category')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Nama Player <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name', $player->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nickname</label>

                        <input type="text" name="nick_name" value="{{ old('nick_name', $player->nick_name) }}"
                            class="form-input @error('nick_name') form-danger @enderror">

                        @error('nick_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>

                        <input type="date" name="birth_date"
                            value="{{ old('birth_date', $player->birth_date?->format('Y-m-d')) }}"
                            class="form-input @error('birth_date') form-danger @enderror">

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>

                        <select name="gender" class="form-select">
                            <option value="">Pilih Gender</option>
                            <option value="male" @selected(old('gender', $player->gender) == 'male')>Male</option>
                            <option value="female" @selected(old('gender', $player->gender) == 'female')>Female</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nationality</label>

                        <input type="text" name="nationality" value="{{ old('nationality', $player->nationality) }}"
                            class="form-input">
                    </div>

                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">Tinggi (cm)</label>

                            <input type="number" name="height" value="{{ old('height', $player->height) }}"
                                class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Berat (kg)</label>

                            <input type="number" name="weight" value="{{ old('weight', $player->weight) }}"
                                class="form-input">
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="form-label">Kaki Dominan</label>

                        <select name="preferred_foot" class="form-select">
                            <option value="">Pilih</option>
                            <option value="right" @selected(old('preferred_foot', $player->preferred_foot) == 'right')>Right</option>
                            <option value="left" @selected(old('preferred_foot', $player->preferred_foot) == 'left')>Left</option>
                            <option value="both" @selected(old('preferred_foot', $player->preferred_foot) == 'both')>Both</option>
                        </select>
                    </div>

                </div>
                {{-- RIGHT COLUMN --}}
                <div>

                    <div class="form-group">
                        <label class="form-label">
                            Posisi Utama <span class="text-error-500">*</span>
                        </label>

                        <select name="id_primary_position"
                            class="form-select @error('id_primary_position') form-danger @enderror" required>
                            <option value="">Pilih Posisi Utama</option>
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

                    <div class="form-group">
                        <label class="form-label">Posisi Kedua</label>

                        <select name="id_secondary_position"
                            class="form-select @error('id_secondary_position') form-danger @enderror">
                            <option value="">Tidak ada</option>
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

                        <p class="form-helper">Opsional. Tidak boleh sama dengan posisi utama.</p>

                        @error('id_secondary_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Player</label>

                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $player->status) == 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $player->status) == 'inactive')>Inactive</option>
                            <option value="graduated" @selected(old('status', $player->status) == 'graduated')>Graduated</option>
                            <option value="left" @selected(old('status', $player->status) == 'left')>Left</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Foto Player
                        </label>


                        <div class="form-file-upload" x-data="{ imagePreview: '{{ $player->photo ? asset('storage/' . $player->photo) : '' }}' }">

                            <input type="file" name="photo" accept="image/*"
                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                @change="
                                const file=$event.target.files[0];
                                if(file){
                                    const reader=new FileReader();
                                    reader.onload=(e)=>imagePreview=e.target.result;
                                    reader.readAsDataURL(file);
                                }
                            ">

                            <div x-show="!imagePreview" class="empty-state">

                                <span class="avatar avatar-lg mb-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M12 16V8M8 12L12 8L16 12M3 15V18C3 18.5 3.2 19 3.6 19.4C4 19.8 4.5 20 5 20H19C19.5 20 20 19.8 20.4 19.4C20.8 19 21 18.5 21 18V15"
                                            stroke="currentColor" stroke-width="1.8" />
                                    </svg>
                                </span>

                                <p class="empty-title">
                                    Klik untuk mengganti foto player
                                </p>

                                <p class="empty-description">
                                    JPG, PNG, WEBP maksimal 2MB
                                </p>

                            </div>

                            <div x-show="imagePreview" x-cloak class="flex flex-col items-center">
                                <div class="avatar avatar-xl avatar-square mb-4">
                                    <img :src="imagePreview" class="h-full w-full object-cover">
                                </div>

                                <span class="link-primary text-xs font-semibold">
                                    Ganti Foto
                                </span>
                            </div>

                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Catatan
                        </label>

                        <textarea name="notes" rows="4" placeholder="Tambahkan catatan player" class="form-textarea">{{ old('notes', $player->notes) }}</textarea>

                        @error('notes')
                            <span class="form-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Update Player
                </button>

            </div>

        </form>

    </div>

@endsection
