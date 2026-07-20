@extends('layouts.app', ['page' => 'academy'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">
                    Buat Akun Owner
                </h3>

                <p class="card-description">
                    Membuat akun login untuk <strong>{{ $academy->name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('academies.account.store', $academy) }}" method="POST">
            @csrf

            <div class="p-5">

                <div class="form-group">
                    <label class="form-label">
                        Email <span class="text-error-500">*</span>
                    </label>

                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input @error('email') form-danger @enderror">

                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Password <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password" class="form-input @error('password') form-danger @enderror">

                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Konfirmasi Password <span class="text-error-500">*</span>
                    </label>

                    <input type="password" name="password_confirmation" class="form-input">

                    @error('password_confirmation')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-5">
                    <a href="{{ route('academies.show', $academy) }}" class="btn btn-secondary">
                        Batal
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Buat Akun
                    </button>
                </div>

            </div>

        </form>

    </div>

@endsection
