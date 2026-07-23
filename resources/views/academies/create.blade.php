@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">{{ __('Informasi Profil Academy') }}</h3>
                <p class="card-description">{{ __('Masukkan detail lengkap untuk mendaftarkan akademi baru.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>

        </div>

        <form action="{{ route('academies.store') }}" method="POST" enctype="multipart/form-data">

            @csrf

            <div class="form-row">

                {{-- Left Column --}}
                <div>

                    {{-- Name --}}
                    <div class="form-group">

                        <label for="name" class="form-label">
                            {{ __('Nama Academy') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            placeholder="{{ __('Masukkan nama akademi') }}"
                            class="form-input @error('name') form-danger @elseif(old('name')) form-success @enderror"
                            required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror


                    </div>

                    {{-- Code --}}
                    <div class="form-group">

                        <label for="code" class="form-label">
                            {{ __('Kode Academy') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="code" name="code" value="{{ old('code') }}"
                            placeholder="{{ __('Contoh: FAOS') }}" class="form-input @error('code') form-danger @enderror" required>

                        @error('code')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Tagline --}}
                    <div class="form-group">

                        <label for="tagline" class="form-label">
                            {{ __('Tagline / Slogan') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline') }}"
                            placeholder="{{ __('Contoh: Maju Bersama Sepakbola') }}"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Phone --}}
                    <div class="form-group">

                        <label for="phone" class="form-label">
                            {{ __('Nomor Telepon') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                            placeholder="{{ __('Contoh: 08XX34567XX') }}" class="form-input @error('phone') form-danger @enderror"
                            required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Email --}}
                    <div class="form-group">

                        <label for="email" class="form-label">
                            {{ __('Email') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                            placeholder="{{ __('Contoh: info@akademi.com') }}" class="form-input @error('email') form-danger @enderror"
                            required>

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Status --}}
                    <div class="form-group">

                        <label class="form-label">
                            {{ __('Status Aktif') }}
                        </label>

                        <div x-data="{ switcherOn: {{ old('status', true) ? 'true' : 'false' }} }">

                            <label class="flex cursor-pointer items-center">

                                <input type="checkbox" name="status" value="1" class="sr-only" :checked="switcherOn"
                                    @change="switcherOn=!switcherOn">

                                <div class="form-toggle" :class="switcherOn && 'form-toggle-active'">
                                    <span class="form-toggle-dot" :class="switcherOn && 'form-toggle-checked'"></span>
                                </div>

                                <span class="ml-3 text-sm text-gray-500 dark:text-gray-400"
                                    x-text="switcherOn ? '{{ __('Aktif') }}' : '{{ __('Nonaktif') }}'"></span>

                            </label>

                        </div>

                    </div>

                    {{-- Subscription --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">

                        <h4 class="section-title mb-4">{{ __('Informasi Langganan') }}</h4>

                        {{-- Tipe Langganan --}}
                        <div class="form-group">
                            <label for="subscription_type" class="form-label">
                                {{ __('Tipe Langganan') }} <span class="text-error-500">*</span>
                            </label>

                            <select id="subscription_type" name="subscription_type"
                                class="form-select @error('subscription_type') form-danger @enderror" required>
                                <option value="">{{ __('Pilih Tipe Langganan') }}</option>
                                @foreach ($subscriptionTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subscription_type') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @error('subscription_type')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Biaya Langganan --}}
                        <div class="form-group">
                            <label for="subscription_fee" class="form-label">
                                {{ __('Biaya Langganan (Rp)') }} <span class="text-error-500">*</span>
                            </label>

                            <x-currency-input name="subscription_fee" id="subscription_fee"
                                :value="old('subscription_fee')" placeholder="{{ __('Contoh: 500.000') }}"
                                :class="$errors->has('subscription_fee') ? 'form-danger' : ''" required />

                            @error('subscription_fee')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Mulai & Berakhir --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            <div class="form-group">
                                <label for="subscription_started_at" class="form-label">
                                    {{ __('Mulai Langganan') }} <span class="text-error-500">*</span>
                                </label>

                                <input type="date" id="subscription_started_at" name="subscription_started_at"
                                    value="{{ old('subscription_started_at', now()->format('Y-m-d')) }}"
                                    class="form-input @error('subscription_started_at') form-danger @enderror" required>

                                @error('subscription_started_at')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="subscription_ends_at" class="form-label">
                                    {{ __('Berakhir Langganan') }} <span class="text-error-500">*</span>
                                </label>

                                <input type="date" id="subscription_ends_at" name="subscription_ends_at"
                                    value="{{ old('subscription_ends_at') }}"
                                    class="form-input @error('subscription_ends_at') form-danger @enderror" required>

                                @error('subscription_ends_at')
                                    <span class="form-error">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                    </div>

                </div>


                {{-- Right Column --}}
                <div>

                    {{-- Logo --}}
                    <x-logo-upload-field />

                    {{-- Logo Sidebar (Wordmark) --}}
                    <x-logo-upload-field name="logo_sidebar" :current-logo-url="null"
                        :label="__('Logo Sidebar (Wordmark)')"
                        :help-text="__('PNG, JPG, JPEG, WEBP maksimal 2MB -- akan diminta crop rasio lebar. Dipakai di sidebar & header saat sidebar diperluas; kalau belum diupload, sidebar menampilkan nama academy sebagai gantinya.')"
                        :crop-title="__('Sesuaikan Logo Sidebar')"
                        :crop-description="__('Geser & perbesar untuk memilih area logo (rasio lebar).')"
                        :aspect-ratio="3.77" :output-width="980" :output-height="260"
                        preview-class="flex h-16 w-40 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/5" />

                    {{-- Warna Utama --}}
                    <div class="form-group">

                        <label for="primary_color" class="form-label">
                            {{ __('Warna Utama Sistem') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="color" id="primary_color" name="primary_color"
                            value="{{ old('primary_color', '#465fff') }}"
                            class="form-input h-11 w-20 cursor-pointer p-1 @error('primary_color') form-danger @enderror"
                            required>

                        <p class="mt-1 text-xs text-gray-400">
                            {{ __('Dipakai untuk warna tombol, link, dan aksen utama tampilan sistem academy ini.') }}
                        </p>

                        @error('primary_color')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Address --}}
                    <div class="form-group">

                        <label for="address" class="form-label">
                            {{ __('Alamat') }} <span class="text-error-500">*</span>
                        </label>

                        <textarea id="address" name="address" rows="3" placeholder="{{ __('Masukkan alamat lengkap akademi') }}"
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address') }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Description --}}
                    <div class="form-group">

                        <label for="description" class="form-label">
                            {{ __('Deskripsi') }}
                        </label>

                        <textarea id="description" name="description" rows="3"
                            placeholder="{{ __('Jelaskan secara singkat mengenai profil akademi Anda') }}"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Buat Akun Owner --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">

                        <h4 class="section-title mb-4">{{ __('Buat Akun Owner') }}</h4>

                        <div x-data="{ createAccount: false }">

                            <input type="hidden" name="create_account" :value="createAccount ? 1 : 0">

                            <label class="flex cursor-pointer items-center">

                                <input type="checkbox" class="sr-only" @change="createAccount=!createAccount">

                                <div class="form-toggle" :class="createAccount && 'form-toggle-active'">
                                    <span class="form-toggle-dot" :class="createAccount && 'form-toggle-checked'">
                                    </span>
                                </div>

                                <span class="ml-3 text-sm text-gray-500" x-text="createAccount ? '{{ __('Aktif') }}' : '{{ __('Nonaktif') }}'">
                                </span>

                            </label>

                            <div x-show="createAccount" x-transition class="mt-4 space-y-3">

                                <div>
                                    <label class="form-label">
                                        {{ __('Email Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                                        placeholder="{{ __('Email akun Owner') }}"
                                        class="form-input @error('owner_email') form-danger @enderror">

                                    @error('owner_email')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Password') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="password" name="owner_password" placeholder="{{ __('Password') }}"
                                        class="form-input @error('owner_password') form-danger @enderror">

                                    @error('owner_password')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Konfirmasi Password') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="password" name="owner_password_confirmation"
                                        placeholder="{{ __('Konfirmasi Password') }}" class="form-input">

                                    @error('owner_password_confirmation')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Nama Lengkap Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="text" name="owner_full_name" value="{{ old('owner_full_name') }}"
                                        class="form-input @error('owner_full_name') form-danger @enderror">

                                    @error('owner_full_name')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Jenis Kelamin Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <select name="owner_gender" class="form-select @error('owner_gender') form-danger @enderror">
                                        <option value="">{{ __('Pilih Jenis Kelamin') }}</option>
                                        <option value="male" @selected(old('owner_gender') === 'male')>{{ __('Laki-laki') }}</option>
                                        <option value="female" @selected(old('owner_gender') === 'female')>{{ __('Perempuan') }}</option>
                                    </select>

                                    @error('owner_gender')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Tempat Lahir Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="text" name="owner_birth_place" value="{{ old('owner_birth_place') }}"
                                        class="form-input @error('owner_birth_place') form-danger @enderror">

                                    @error('owner_birth_place')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Tanggal Lahir Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="date" name="owner_birth_date" value="{{ old('owner_birth_date') }}"
                                        class="form-input @error('owner_birth_date') form-danger @enderror">

                                    @error('owner_birth_date')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label">
                                        {{ __('Nomor Telepon Owner') }} <span class="text-error-500" x-show="createAccount">*</span>
                                    </label>

                                    <input type="text" name="owner_phone" value="{{ old('owner_phone') }}"
                                        class="form-input @error('owner_phone') form-danger @enderror">

                                    @error('owner_phone')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <button type="reset" class="btn btn-secondary">
                    {{ __('Reset') }}
                </button>

                <button type="submit" class="btn btn-primary">
                    {{ __('Simpan Academy') }}
                </button>

            </div>

        </form>

    </div>

@endsection
