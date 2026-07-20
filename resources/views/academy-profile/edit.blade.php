@extends('layouts.app', ['page' => 'academy-profile'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Profil Academy') }}</h3>
                <p class="card-description">{{ __('Kelola informasi profil academy Anda. Kode academy, status, dan informasi langganan hanya dapat diubah oleh Super Admin.') }}</p>
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
                            {{ __('Nama Academy') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name', $academy->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="tagline" class="form-label">
                            {{ __('Tagline / Slogan') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $academy->tagline) }}"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            {{ __('Nomor Telepon') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone', $academy->phone) }}"
                            class="form-input @error('phone') form-danger @enderror" required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            {{ __('Email') }} <span class="text-error-500">*</span>
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

                    <x-logo-upload-field :current-logo-url="$academy->logo ? asset('storage/' . $academy->logo) : null" />

                    <div class="form-group">

                        <label for="primary_color" class="form-label">
                            {{ __('Warna Utama Sistem') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="color" id="primary_color" name="primary_color"
                            value="{{ old('primary_color', $academy->primary_color ?? '#465fff') }}"
                            class="form-input h-11 w-20 cursor-pointer p-1 @error('primary_color') form-danger @enderror"
                            required>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('Dipakai untuk warna tombol, link, dan aksen utama tampilan sistem academy ini.') }}
                        </p>

                        @error('primary_color')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">
                            {{ __('Alamat') }} <span class="text-error-500">*</span>
                        </label>

                        <textarea id="address" name="address" rows="3"
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address', $academy->address) }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">{{ __('Deskripsi') }}</label>

                        <textarea id="description" name="description" rows="3"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $academy->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">
                <button type="submit" class="btn btn-primary">{{ __('Simpan Perubahan') }}</button>
            </div>

        </form>

    </div>

@endsection
