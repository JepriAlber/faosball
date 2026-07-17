@extends('layouts.app', ['page' => 'player-positions'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">Informasi Posisi Pemain</h3>
                <p class="card-description">Perbarui detail posisi pemain.</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('player-positions.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>

        <form action="{{ route('player-positions.update', $playerPosition) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">

                <div>

                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">
                                Kode <span class="text-error-500">*</span>
                            </label>

                            <input type="text" name="code" value="{{ old('code', $playerPosition->code) }}"
                                maxlength="10" class="form-input @error('code') form-danger @enderror" required>

                            <p class="form-helper">Singkatan standar sepak bola. Otomatis jadi huruf besar.</p>

                            @error('code')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                Urutan <span class="text-error-500">*</span>
                            </label>

                            <input type="number" name="sort_order" value="{{ old('sort_order', $playerPosition->sort_order) }}"
                                min="0" max="9999" class="form-input @error('sort_order') form-danger @enderror" required>

                            <p class="form-helper">Makin kecil makin atas. Kiper 1, bek 10-an, gelandang 20-an,
                                penyerang 30-an.</p>

                            @error('sort_order')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Nama Posisi <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="name" value="{{ old('name', $playerPosition->name) }}"
                            class="form-input @error('name') form-danger @enderror" required>

                        @error('name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Kelompok <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="position_group" value="{{ old('position_group', $playerPosition->position_group) }}"
                            list="position-group-options"
                            class="form-input @error('position_group') form-danger @enderror" required>

                        <datalist id="position-group-options">
                            @foreach ($existingGroups as $group)
                                <option value="{{ $group }}"></option>
                            @endforeach
                        </datalist>

                        <p class="form-helper">Dipakai untuk mengelompokkan pilihan posisi di form Player.</p>

                        @error('position_group')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>

                        <textarea name="description" rows="3"
                            class="form-textarea @error('description') form-danger @enderror">{{ old('description', $playerPosition->description) }}</textarea>

                        @error('description')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group"
                        x-data="{ isActive: {{ old('status', $playerPosition->status) ? 'true' : 'false' }} }">

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

                <a href="{{ route('player-positions.index') }}" class="btn btn-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-primary">
                    Update Posisi
                </button>

            </div>

        </form>

    </div>

@endsection
