@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Profil Player</h3>
                <p class="card-description">Masukkan detail lengkap untuk mendaftarkan pemain baru.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('players.store') }}" method="POST" enctype="multipart/form-data"
            x-data="{
                isSuperAdmin: @js($isSuperAdmin),
                academyId: @js(old('id_academy', '')),
                birthDate: @js(old('birth_date', '')),
                playerTypeId: @js(old('id_player_type', '')),
                playerCategoryId: @js(old('id_player_category', '')),
                types: @js($playerTypes),
                categories: @js($playerCategories),

                // Super Admin: saring sesuai academy yang dipilih di form ini.
                // User academy: Controller sudah menyaringnya, pakai apa adanya.
                get availableTypes() {
                    return this.isSuperAdmin
                        ? this.types.filter(type => type.id_academy === this.academyId)
                        : this.types;
                },

                get availableCategories() {
                    return this.isSuperAdmin
                        ? this.categories.filter(category => category.id_academy === this.academyId)
                        : this.categories;
                },

                get age() {
                    if (! this.birthDate) return null;
                    const birth = new Date(this.birthDate);
                    if (isNaN(birth)) return null;
                    const today = new Date();
                    let age = today.getFullYear() - birth.getFullYear();
                    const monthDiff = today.getMonth() - birth.getMonth();
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                        age--;
                    }
                    return age;
                },

                get suggestedCategory() {
                    if (this.age === null) return null;
                    return this.availableCategories.find(
                        category => this.age >= category.min_age && this.age <= category.max_age
                    ) ?? null;
                },

                get selectedCategory() {
                    return this.categories.find(
                        category => category.id_player_category === this.playerCategoryId
                    ) ?? null;
                },

                // Peringatan lunak, TIDAK memblokir simpan. Lihat issue2.md Bagian 4.2.
                get ageOutsideRange() {
                    const category = this.selectedCategory;
                    if (! category || this.age === null) return false;
                    return this.age < category.min_age || this.age > category.max_age;
                },

                applySuggestion() {
                    this.playerCategoryId = this.suggestedCategory
                        ? this.suggestedCategory.id_player_category
                        : '';
                },
            }">
            @csrf

            <div class="form-row">

                {{-- LEFT COLUMN: Scope, Identitas, Klasifikasi/Relasi Wajib --}}
                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                Academy <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" x-model="academyId"
                                @change="playerTypeId = ''; playerCategoryId = ''"
                                class="form-select @error('id_academy') form-danger @enderror" required>
                                <option value="">Pilih Academy</option>
                                @foreach ($academies as $academy)
                                    <option value="{{ $academy->id_academy }}">{{ $academy->name }}</option>
                                @endforeach
                            </select>

                            @error('id_academy')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    {{-- Nama --}}
                    <div class="form-group">
                        <label class="form-label">
                            Nama Player <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama player"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nickname --}}
                    <div class="form-group">
                        <label class="form-label">Nickname</label>

                        <input type="text" name="nick_name" value="{{ old('nick_name') }}"
                            placeholder="Nama panggilan player"
                            class="form-input @error('nick_name') form-danger @enderror">

                        @error('nick_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Type Player --}}
                    <div class="form-group">
                        <label class="form-label">
                            Type Player <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_type" x-model="playerTypeId"
                            class="form-select @error('id_player_type') form-danger @enderror" required>
                            <option value="">Pilih Type Player</option>
                            <template x-for="type in availableTypes" :key="type.id_player_type">
                                <option :value="type.id_player_type" x-text="type.name"></option>
                            </template>
                        </select>

                        <p x-show="isSuperAdmin && academyId && availableTypes.length === 0" x-cloak class="form-error">
                            Academy ini belum punya type player. Buat dulu lewat menu Player Type.
                        </p>

                        @error('id_player_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kategori Umur --}}
                    <div class="form-group">
                        <label class="form-label">
                            Kategori Umur <span class="text-error-500">*</span>
                        </label>

                        <select name="id_player_category" x-model="playerCategoryId"
                            class="form-select @error('id_player_category') form-danger @enderror" required>
                            <option value="">Pilih Kategori Umur</option>
                            <template x-for="category in availableCategories" :key="category.id_player_category">
                                <option :value="category.id_player_category"
                                    x-text="`${category.name} (${category.min_age}-${category.max_age} th)`"></option>
                            </template>
                        </select>

                        {{-- Saran: tampil hanya kalau ada saran DAN belum dipilih --}}
                        <p x-show="suggestedCategory && suggestedCategory.id_player_category !== playerCategoryId"
                            x-cloak class="form-helper">
                            Saran untuk umur <span x-text="age"></span> tahun:
                            <button type="button" class="link-primary font-medium" @click="applySuggestion()">
                                <span x-text="suggestedCategory?.name"></span> — pakai saran ini
                            </button>
                        </p>

                        {{-- Peringatan LUNAK: memberi tahu, tidak memblokir. Lihat issue2.md Bagian 4.2. --}}
                        <p x-show="ageOutsideRange" x-cloak class="form-helper text-warning-500">
                            Umur pemain (<span x-text="age"></span> th) di luar rentang kategori ini
                            (<span x-text="selectedCategory?.min_age"></span>–<span x-text="selectedCategory?.max_age"></span>
                            th). Ini diperbolehkan — pastikan memang disengaja.
                        </p>

                        <p x-show="isSuperAdmin && academyId && availableCategories.length === 0" x-cloak
                            class="form-error">
                            Academy ini belum punya kategori umur. Buat dulu lewat menu Player Category.
                        </p>

                        @error('id_player_category')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Posisi Utama --}}
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
                                        <option value="{{ $position->id_player_position }}" @selected(old('id_primary_position') === $position->id_player_position)>
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
                        <label class="form-label">Posisi Kedua</label>

                        <select name="id_secondary_position"
                            class="form-select @error('id_secondary_position') form-danger @enderror">
                            <option value="">Tidak ada</option>
                            @foreach ($playerPositions->groupBy('position_group') as $group => $positions)
                                <optgroup label="{{ $group }}">
                                    @foreach ($positions as $position)
                                        <option value="{{ $position->id_player_position }}" @selected(old('id_secondary_position') === $position->id_player_position)>
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

                </div>

                {{-- RIGHT COLUMN: Deskriptif, Media, Status, Section Terpisah --}}
                <div>

                    {{-- Tanggal Lahir --}}
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>

                        <input type="date" name="birth_date" x-model="birthDate"
                            @change="if (! playerCategoryId) applySuggestion()"
                            class="form-input @error('birth_date') form-danger @enderror">

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label class="form-label">Gender</label>

                        <select name="gender" class="form-select">
                            <option value="">Pilih Gender</option>
                            <option value="male" @selected(old('gender') == 'male')>Male</option>
                            <option value="female" @selected(old('gender') == 'female')>Female</option>
                        </select>
                    </div>

                    {{-- Nationality --}}
                    <div class="form-group">
                        <label class="form-label">Nationality</label>

                        <input type="text" name="nationality" value="{{ old('nationality', 'Indonesia') }}"
                            class="form-input">
                    </div>

                    {{-- Tinggi & Berat --}}
                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">Tinggi (cm)</label>

                            <input type="number" name="height" value="{{ old('height') }}" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Berat (kg)</label>

                            <input type="number" name="weight" value="{{ old('weight') }}" class="form-input">
                        </div>

                    </div>

                    {{-- Kaki Dominan --}}
                    <div class="form-group">
                        <label class="form-label">Kaki Dominan</label>

                        <select name="preferred_foot" class="form-select">
                            <option value="">Pilih</option>
                            <option value="right" @selected(old('preferred_foot') == 'right')>Right</option>
                            <option value="left" @selected(old('preferred_foot') == 'left')>Left</option>
                            <option value="both" @selected(old('preferred_foot') == 'both')>Both</option>
                        </select>
                    </div>

                    {{-- Foto --}}
                    <x-player-photo-field />

                    {{-- Catatan --}}
                    <div class="form-group">
                        <label class="form-label">
                            Catatan
                        </label>

                        <textarea name="notes" rows="3" placeholder="Tambahkan catatan player" class="form-textarea">{{ old('notes') }}</textarea>

                        @error('notes')
                            <span class="form-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    {{-- Tanggal Bergabung --}}
                    <div class="form-group">
                        <label class="form-label">Tanggal Bergabung</label>

                        <input type="date" name="join_date" value="{{ old('join_date') }}" class="form-input">
                    </div>

                    {{-- Status --}}
                    <div class="form-group">
                        <label class="form-label">Status Player</label>
                        <select name="status" class="form-select @error('status') form-danger @enderror">
                            <option value="active" @selected(old('status', 'active') == 'active')>Active</option>
                            <option value="inactive" @selected(old('status') == 'inactive')>Inactive</option>
                            <option value="graduated" @selected(old('status') == 'graduated')>Graduated</option>
                            <option value="left" @selected(old('status') == 'left')>Left</option>
                        </select>
                        @error('status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Buat Akun Player --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">

                        <h4 class="section-title mb-4">Buat Akun Player</h4>

                        <div x-data="{ createAccount: false }">

                            <input type="hidden" name="create_account" :value="createAccount ? 1 : 0">

                            <label class="flex cursor-pointer items-center">

                                <input type="checkbox" class="sr-only" @change="createAccount=!createAccount">

                                <div class="form-toggle" :class="createAccount && 'form-toggle-active'">
                                    <span class="form-toggle-dot" :class="createAccount && 'form-toggle-checked'">
                                    </span>
                                </div>

                                <span class="ml-3 text-sm text-gray-500" x-text="createAccount ? 'Aktif':'Nonaktif'">
                                </span>

                            </label>

                            <div x-show="createAccount" x-transition class="mt-4 space-y-3">

                                <div>
                                    <label class="form-label">
                                        Email Akun <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="email" name="email" value="{{ old('email') }}"
                                        placeholder="Email akun" class="form-input @error('email') form-danger @enderror">

                                    @error('email')
                                        <span class="form-error">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        Password <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="password" name="password" placeholder="Password"
                                        class="form-input @error('password') form-danger @enderror">

                                    @error('password')
                                        <span class="form-error">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        Konfirmasi Password <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="password" name="password_confirmation" placeholder="Konfirmasi Password"
                                        class="form-input">

                                    @error('password_confirmation')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Simpan Player
                </button>

            </div>

        </form>

    </div>

@endsection
