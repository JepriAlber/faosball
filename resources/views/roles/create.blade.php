@extends('layouts.app', ['page' => 'role'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Role</h3>
                <p class="card-description">
                    Buat role baru dan tentukan permission yang dimiliki.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('roles.store') }}" method="POST" x-data="rolePermissionForm">

            @csrf

            @if ($isSuperAdmin)
                <div class="form-group">

                    <label class="form-label">Academy</label>

                    <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror">
                        <option value="">— Role System —</option>
                        @foreach ($academies as $academy)
                            <option value="{{ $academy->id_academy }}" @selected(old('id_academy') === $academy->id_academy)>
                                {{ $academy->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('id_academy')
                        <span class="form-error">{{ $message }}</span>
                    @enderror

                </div>
            @endif

            <div class="form-group">

                <label class="form-label">
                    Nama Role
                    <span class="text-error-500">*</span>
                </label>

                <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh : Finance Manager"
                    class="form-input @error('name') form-danger @elseif(old('name')) form-success @enderror"
                    required>


                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror

            </div>

            <div class="mt-8">

                <div class="mb-4">
                    <h4 class="card-title text-base">
                        Hak Akses
                    </h4>

                    <p class="card-description">
                        Pilih permission yang dimiliki oleh role ini.
                    </p>
                </div>

                <div
                    class="mb-6 flex flex-col gap-4 rounded-xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-800 dark:bg-brand-950 md:flex-row md:items-center md:justify-between">

                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            Permission
                        </h4>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Pilih seluruh permission atau atur setiap modul secara terpisah.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">

                        <button type="button" class="btn btn-primary" @click="selectAll()">
                            Pilih Semua
                        </button>

                        <button type="button" class="btn btn-secondary" @click="unselectAll()">
                            Hapus Semua
                        </button>

                    </div>

                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

                    @foreach ($permissionGroups as $module => $permissions)
                        <x-forms.permission-group :module="$module" :permissions="$permissions" :selected="old('permissions', [])" />
                    @endforeach

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-800">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Simpan Role
                </button>

            </div>

        </form>

    </div>

@endsection
