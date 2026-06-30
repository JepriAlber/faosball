@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">
                    Buat Akun Player
                </h3>
                <p class="card-description">
                    Membuat akun login untuk {{ $player->name }}.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>


        <form action="{{ route('players.account.store', $player->id_player) }}" method="POST">
            @csrf

            <div class="p-5">

                <div class="form-group">
                    <label class="form-label">
                        Email
                    </label>

                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input @error('email') form-danger @enderror">

                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Password
                    </label>

                    <input type="password" name="password" class="form-input @error('password') form-danger @enderror">

                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Konfirmasi Password
                    </label>

                    <input type="password" name="password_confirmation" class="form-input">

                </div>


                <div class="mt-6 flex justify-end gap-3 border-t pt-5">

                    <a href="{{ route('players.index') }}" class="btn btn-secondary">
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
