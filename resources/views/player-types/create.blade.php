@extends('layouts.app', ['page' => 'player-types'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Player Type</h3>
                <p class="card-description">Tambahkan jenis pemain baru untuk academy.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('player-types.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('player-types.store') }}" method="POST">
            @csrf

            <div class="form-row">

                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                Academy <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror"
                                required>
                                <option value="">Pilih Academy</option>
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
                            Nama Type <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name') }}"
                            placeholder="Contoh: Reguler, Beasiswa, Trial"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>

                        <textarea name="description" rows="3" placeholder="Keterangan singkat tentang type ini"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description') }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    <div class="form-group" x-data="{ isBillable: {{ old('is_billable', 1) ? 'true' : 'false' }} }">

                        <label class="form-label">Tagihan Iuran / SPP</label>

                        <input type="hidden" name="is_billable" :value="isBillable ? 1 : 0">

                        <label class="flex cursor-pointer items-center">

                            <input type="checkbox" class="sr-only" :checked="isBillable" @change="isBillable = !isBillable">

                            <div class="form-toggle" :class="isBillable && 'form-toggle-active'">
                                <span class="form-toggle-dot" :class="isBillable && 'form-toggle-checked'"></span>
                            </div>

                            <span class="ml-3 text-sm text-gray-500"
                                x-text="isBillable ? 'Player dengan type ini ditagih iuran/SPP' : 'Player dengan type ini tidak ditagih'">
                            </span>

                        </label>

                        @error('is_billable')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                    <div class="form-group" x-data="{ isActive: {{ old('status', 1) ? 'true' : 'false' }} }">

                        <label class="form-label">Status</label>

                        <input type="hidden" name="status" :value="isActive ? 1 : 0">

                        <label class="flex cursor-pointer items-center">

                            <input type="checkbox" class="sr-only" :checked="isActive" @change="isActive = !isActive">

                            <div class="form-toggle" :class="isActive && 'form-toggle-active'">
                                <span class="form-toggle-dot" :class="isActive && 'form-toggle-checked'"></span>
                            </div>

                            <span class="ml-3 text-sm text-gray-500" x-text="isActive ? 'Aktif' : 'Nonaktif'">
                            </span>

                        </label>

                        @error('status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <button type="reset" class="btn btn-secondary">
                    Reset
                </button>

                <button type="submit" class="btn btn-primary">
                    Simpan Player Type
                </button>

            </div>

        </form>

    </div>

@endsection
