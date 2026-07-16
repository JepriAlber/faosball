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

        <form action="{{ route('players.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-row">

                {{-- LEFT COLUMN --}}
                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                Academy <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror"
                                required>
                                <option value="">Pilih Academy</option>
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
                            Nama Player <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama player"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>


                    <div class="form-group">
                        <label class="form-label">Nickname</label>

                        <input type="text" name="nick_name" value="{{ old('nick_name') }}"
                            placeholder="Nama panggilan player"
                            class="form-input @error('nick_name') form-danger @enderror">

                        @error('nick_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>

                        <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                            class="form-input @error('birth_date') form-danger @enderror">

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>

                        <select name="gender" class="form-select">
                            <option value="">Pilih Gender</option>
                            <option value="male" @selected(old('gender') == 'male')>Male</option>
                            <option value="female" @selected(old('gender') == 'female')>Female</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nationality</label>

                        <input type="text" name="nationality" value="{{ old('nationality', 'Indonesia') }}"
                            class="form-input">
                    </div>

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

                    <div class="form-group">
                        <label class="form-label">Kaki Dominan</label>

                        <select name="preferred_foot" class="form-select">
                            <option value="">Pilih</option>
                            <option value="right" @selected(old('preferred_foot') == 'right')>Right</option>
                            <option value="left" @selected(old('preferred_foot') == 'left')>Left</option>
                            <option value="both" @selected(old('preferred_foot') == 'both')>Both</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label class="form-label">
                            Posisi Utama <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="primary_position" value="{{ old('primary_position') }}"
                            placeholder="Contoh: Forward, Midfielder"
                            class="form-input @error('primary_position') form-danger @enderror" required>

                        @error('primary_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Posisi Kedua</label>

                        <input type="text" name="secondary_position" value="{{ old('secondary_position') }}"
                            class="form-input">
                    </div>

                </div>

                {{-- RIGHT COLUMN --}}
                <div>

                    <div class="form-group">
                        <label class="form-label">Tanggal Bergabung</label>

                        <input type="date" name="join_date" value="{{ old('join_date') }}" class="form-input">
                    </div>

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

                    <div class="form-group" x-data="{ imagePreview: null }">

                        <label class="form-label">
                            Foto Player
                        </label>

                        <div class="form-file-upload">

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
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>

                                </span>

                                <p class="empty-title">
                                    Klik untuk upload foto player
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

                        @error('photo')
                            <span class="form-error">
                                {{ $message }}
                            </span>
                        @enderror

                    </div>

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


                    <div class="form-group">

                        <label class="form-label">
                            Buat Akun Player
                        </label>


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

                                    <input type="email" name="email" value="{{ old('email') }}"
                                        placeholder="Email akun" class="form-input @error('email') form-danger @enderror">

                                    @error('email')
                                        <span class="form-error">
                                            {{ $message }}
                                        </span>
                                    @enderror

                                </div>

                                <div>

                                    <input type="password" name="password" placeholder="Password"
                                        class="form-input @error('password') form-danger @enderror">

                                    @error('password')
                                        <span class="form-error">
                                            {{ $message }}
                                        </span>
                                    @enderror

                                </div>

                                <div>
                                    <input type="password" name="password_confirmation" placeholder="Konfirmasi Password"
                                        class="form-input">
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
