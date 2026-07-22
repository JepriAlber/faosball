@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">
                    {{ __('Buat Akun Staff') }}
                </h3>

                <p class="card-description">
                    {{ __('Membuat akun login untuk') }} <strong>{{ $staff->full_name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('staff.account.store', $staff) }}" method="POST">
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
                </div>

                <div class="form-group">
                    <label class="form-label">
                        {{ __('Role') }} <span class="text-error-500">*</span>
                    </label>

                    <select name="role_id" class="form-select @error('role_id') form-danger @enderror">
                        <option value="">{{ __('Pilih Role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}"
                                @selected((string) old('role_id', $staff->position->role_id) === (string) $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('role_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t pt-5">

                    <a href="{{ route('staff.index') }}" class="btn btn-secondary">
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
