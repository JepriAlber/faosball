@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <div x-data="{ pageName: @js($title) }">
        @include('partials.breadcrumb')
    </div>

    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">Ubah Profil Academy</h3>
                <p class="card-description">Perbarui rincian informasi untuk akademi {{ $academy->name }}.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academy.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>

        </div>

        <form action="{{ route('academy.update', $academy->id_academy) }}" method="POST" enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <div class="form-row">

                {{-- Left Column --}}
                <div>

                    {{-- Name --}}
                    <div class="form-group">

                        <label for="name" class="form-label">
                            Nama Academy <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name', $academy->name) }}"
                            placeholder="Masukkan nama akademi" class="form-input @error('name') form-danger @enderror"
                            required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Tagline --}}
                    <div class="form-group">

                        <label for="tagline" class="form-label">
                            Tagline / Slogan <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $academy->tagline) }}"
                            placeholder="Contoh: Maju Bersama Sepakbola"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Phone --}}
                    <div class="form-group">

                        <label for="phone" class="form-label">
                            Nomor Telepon <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone', $academy->phone) }}"
                            placeholder="Contoh: 08123456789" class="form-input @error('phone') form-danger @enderror"
                            required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Email --}}
                    <div class="form-group">

                        <label for="email" class="form-label">
                            Email <span class="text-error-500">*</span>
                        </label>

                        <input type="email" id="email" name="email" value="{{ old('email', $academy->email) }}"
                            placeholder="Contoh: info@akademi.com" class="form-input @error('email') form-danger @enderror"
                            required>

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Status --}}
                    <div class="form-group">

                        <label class="form-label">
                            Status Aktif
                        </label>

                        <div x-data="{ switcherOn: {{ old('status', $academy->status) ? 'true' : 'false' }} }">

                            <label class="flex cursor-pointer items-center">

                                <input type="checkbox" name="status" value="1" class="sr-only" :checked="switcherOn"
                                    @change="switcherOn=!switcherOn">

                                <div class="form-toggle" :class="switcherOn && 'form-toggle-active'">
                                    <span class="form-toggle-dot" :class="switcherOn && 'form-toggle-checked'"></span>
                                </div>

                                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400"
                                    x-text="switcherOn ? 'Aktif' : 'Nonaktif'"></span>

                            </label>

                        </div>

                    </div>

                </div>

                {{-- Right Column --}}
                <div>

                    {{-- Logo --}}
                    <div class="form-group" x-data="{ imagePreview: '{{ $academy->logo ? asset('storage/' . $academy->logo) : '' }}' }">

                        <label class="form-label">
                            Logo Academy
                        </label>

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

                            {{-- Empty State --}}
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
                                    Klik untuk unggah logo
                                </p>

                                <p class="empty-description">
                                    SVG, PNG, JPG, WEBP maksimal 2MB
                                </p>

                            </div>

                            {{-- Preview --}}
                            <div x-show="imagePreview" x-cloak class="flex flex-col items-center">

                                <div class="avatar avatar-lg avatar-square mb-3">
                                    <img :src="imagePreview" class="h-full w-full object-cover">
                                </div>

                                <span class="link-primary text-xs font-semibold">
                                    Ganti gambar
                                </span>

                            </div>

                        </div>

                        @error('logo')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Address --}}
                    <div class="form-group">

                        <label for="address" class="form-label">
                            Alamat <span class="text-error-500">*</span>
                        </label>

                        <textarea id="address" name="address" rows="3" placeholder="Masukkan alamat lengkap akademi"
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address', $academy->address) }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Description --}}
                    <div class="form-group">

                        <label for="description" class="form-label">
                            Deskripsi
                        </label>

                        <textarea id="description" name="description" rows="3"
                            placeholder="Jelaskan secara singkat mengenai profil akademi Anda"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $academy->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

            </div>
            {{-- Submit --}}
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Perbarui Academy
                </button>

            </div>

        </form>

    </div>

@endsection
