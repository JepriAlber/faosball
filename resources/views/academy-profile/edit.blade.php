@extends('layouts.app', ['page' => 'academy-profile'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Profil Academy</h3>
                <p class="card-description">Kelola informasi profil academy Anda. Kode academy, status, dan
                    informasi langganan hanya dapat diubah oleh Super Admin.</p>
            </div>
        </div>

        <form action="{{ route('academy.profile.update') }}" method="POST" enctype="multipart/form-data">

            @csrf
            @method('PATCH')

            <div class="form-row">

                {{-- Left Column --}}
                <div>

                    <div class="form-group">
                        <label for="name" class="form-label">
                            Nama Academy <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name', $academy->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="tagline" class="form-label">
                            Tagline / Slogan <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $academy->tagline) }}"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Nomor Telepon <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone', $academy->phone) }}"
                            class="form-input @error('phone') form-danger @enderror" required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email <span class="text-error-500">*</span>
                        </label>

                        <input type="email" id="email" name="email" value="{{ old('email', $academy->email) }}"
                            class="form-input @error('email') form-danger @enderror" required>

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                {{-- Right Column --}}
                <div>

                    <div class="form-group" x-data="{ imagePreview: '{{ $academy->logo ? asset('storage/' . $academy->logo) : '' }}' }">
                        <label class="form-label">Logo Academy</label>

                        <div class="form-file-upload">
                            <input type="file" id="logo" name="logo"
                                class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" accept="image/*"
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
                                <p class="empty-title">Klik untuk unggah logo</p>
                                <p class="empty-description">SVG, PNG, JPG, WEBP maksimal 2MB</p>
                            </div>

                            <div x-show="imagePreview" x-cloak class="flex flex-col items-center">
                                <div class="avatar avatar-lg avatar-square mb-3">
                                    <img :src="imagePreview" class="h-full w-full object-cover">
                                </div>
                                <span class="link-primary text-xs font-semibold">Ganti gambar</span>
                            </div>
                        </div>

                        @error('logo')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">
                            Alamat <span class="text-error-500">*</span>
                        </label>

                        <textarea id="address" name="address" rows="3"
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address', $academy->address) }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi</label>

                        <textarea id="description" name="description" rows="3"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $academy->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>

        </form>

    </div>

@endsection
