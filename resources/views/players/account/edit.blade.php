@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <x-alert />

    <div class="card">

        <div class="card-header">

            <div>
                <h3 class="card-title">
                    {{ __('Edit Akun Player') }}
                </h3>

                <p class="card-description">
                    {{ __('Mengubah informasi akun login untuk') }} <strong>{{ $player->name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('players.show', $player) }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>

        </div>


        <form action="{{ route('players.account.update', $player) }}" method="POST">

            @csrf
            @method('PUT')


            <div class="p-5">

                <div class="form-group">

                    <label class="form-label">
                        {{ __('Nama Account') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="form-input @error('name') form-danger @enderror">

                    @error('name')
                        <span class="form-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        {{ __('Email') }} <span class="text-error-500">*</span>
                    </label>

                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="form-input @error('email') form-danger @enderror">

                    @error('email')
                        <span class="form-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="mt-6 flex justify-end gap-3 border-t pt-5">

                    <a href="{{ route('players.show', $player) }}" class="btn btn-secondary">
                        {{ __('Batal') }}
                    </a>


                    <button type="submit" class="btn btn-primary">
                        {{ __('Simpan Perubahan') }}
                    </button>

                </div>


            </div>

        </form>

    </div>

@endsection
