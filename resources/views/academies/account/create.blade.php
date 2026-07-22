@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">
                    {{ __('Buat Akun Owner') }}
                </h3>

                <p class="card-description">
                    {{ __('Membuat akun login untuk') }} <strong>{{ $academy->name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('academies.account.store', $academy) }}" method="POST">
            @csrf

            <div class="p-5">

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Email') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input @error('email') form-danger @enderror">

                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Nama Lengkap') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="full_name" value="{{ old('full_name') }}"
                        class="form-input @error('full_name') form-danger @enderror">

                    @error('full_name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Jenis Kelamin') }} <span class="text-error-500">*</span>
                    </label>

                    <select name="gender" class="form-select @error('gender') form-danger @enderror">
                        <option value="">{{ __('Pilih Jenis Kelamin') }}</option>
                        <option value="male" @selected(old('gender') === 'male')>{{ __('Laki-laki') }}</option>
                        <option value="female" @selected(old('gender') === 'female')>{{ __('Perempuan') }}</option>
                    </select>

                    @error('gender')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Tempat Lahir') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="birth_place" value="{{ old('birth_place') }}"
                        class="form-input @error('birth_place') form-danger @enderror">

                    @error('birth_place')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Tanggal Lahir') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                        class="form-input @error('birth_date') form-danger @enderror">

                    @error('birth_date')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Nomor Telepon') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="form-input @error('phone') form-danger @enderror">

                    @error('phone')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Password') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password" class="form-input @error('password') form-danger @enderror">

                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Konfirmasi Password') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password_confirmation" class="form-input">

                    @error('password_confirmation')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-5">
                    <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                        {{ __('Batal') }}
                    </a>

                    <button type="submit" class="btn btn-primary">
                        {{ __('Buat Akun') }}
                    </button>
                </div>

            </div>

        </form>

    </div>

@endsection
