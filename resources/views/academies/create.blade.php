@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">Informasi Profil Academy</h3>
                <p class="card-description">Masukkan detail lengkap untuk mendaftarkan akademi baru.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.index') }}" class="btn btn-secondary">
                    Kembali
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
                            Nama Academy <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            placeholder="Masukkan nama akademi"
                            class="form-input @error('name') form-danger @elseif(old('name')) form-success @enderror"
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

                        <input type="text" id="tagline" name="tagline" value="{{ old('tagline') }}"
                            placeholder="Contoh: Maju Bersama Sepakbola"
                            class="form-input @error('tagline') form-danger @enderror" required>

                        @error('tagline')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- code --}}

                    <div>
                        <label class="form-label">
                            Kode Academy <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="code" value="{{ old('code') }}"
                            class="form-input  @error('code') form-danger @enderror" required placeholder="Contoh: FAOS">

                        @error('code')
                            <span class="form-error"> {{ $message }} </span>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="form-group">

                        <label for="phone" class="form-label">
                            Nomor Telepon <span class="text-error-500">*</span>
                        </label>

                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                            placeholder="Contoh: 08XX34567XX" class="form-input @error('phone') form-danger @enderror"
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

                        <input type="email" id="email" name="email" value="{{ old('email') }}"
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

                        <div x-data="{ switcherOn: {{ old('status', true) ? 'true' : 'false' }} }">

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

                    {{-- Subscription --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">

                        <h4 class="section-title mb-4">Informasi Langganan</h4>

                        {{-- Tipe Langganan --}}
                        <div class="form-group">
                            <label for="subscription_type" class="form-label">
                                Tipe Langganan <span class="text-error-500">*</span>
                            </label>

                            <select id="subscription_type" name="subscription_type"
                                class="form-select @error('subscription_type') form-danger @enderror" required>
                                <option value="">Pilih Tipe Langganan</option>
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
                                Biaya Langganan (Rp) <span class="text-error-500">*</span>
                            </label>

                            <input type="number" id="subscription_fee" name="subscription_fee"
                                value="{{ old('subscription_fee') }}" min="0" step="0.01"
                                placeholder="Contoh: 500000"
                                class="form-input @error('subscription_fee') form-danger @enderror" required>

                            @error('subscription_fee')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Mulai & Berakhir --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            <div class="form-group">
                                <label for="subscription_started_at" class="form-label">
                                    Mulai Langganan <span class="text-error-500">*</span>
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
                                    Berakhir Langganan <span class="text-error-500">*</span>
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
                    <div class="form-group" x-data="{ imagePreview: null }">

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
                            class="form-textarea @error('address') form-danger @enderror" required>{{ old('address') }}</textarea>

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
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Buat Akun Owner --}}
                    <div class="form-group">

                        <label class="form-label">
                            Buat Akun Owner
                        </label>

                        <div x-data="{ createAccount: false }">

                            <input type="hidden" name="create_account" :value="createAccount ? 1 : 0">

                            <label class="flex cursor-pointer items-center">

                                <input type="checkbox" class="sr-only" @change="createAccount=!createAccount">

                                <div class="form-toggle" :class="createAccount && 'form-toggle-active'">
                                    <span class="form-toggle-dot" :class="createAccount && 'form-toggle-checked'">
                                    </span>
                                </div>

                                <span class="ml-3 text-sm text-gray-500" x-text="createAccount ? 'Aktif' : 'Nonaktif'">
                                </span>

                            </label>

                            <div x-show="createAccount" x-transition class="mt-4 space-y-3">

                                <div>
                                    <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                                        placeholder="Email akun Owner"
                                        class="form-input @error('owner_email') form-danger @enderror">

                                    @error('owner_email')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <input type="password" name="owner_password" placeholder="Password"
                                        class="form-input @error('owner_password') form-danger @enderror">

                                    @error('owner_password')
                                        <span class="form-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <input type="password" name="owner_password_confirmation"
                                        placeholder="Konfirmasi Password" class="form-input">
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- Submit --}}
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Simpan Academy
                </button>

            </div>

        </form>

    </div>

@endsection
