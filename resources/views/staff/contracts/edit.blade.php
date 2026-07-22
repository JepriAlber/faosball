@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Edit Kontrak') }}</h3>
                <p class="card-description">
                    {{ __('Perbarui kontrak Draft milik') }} <strong>{{ $staff->full_name }}</strong>.
                </p>
            </div>

            <div class="card-actions">
                <a href="{{ route('staff.show', $staff) }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('staff.contracts.update', [$staff, $contract]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">

                <div>

                    {{-- Employment Type --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Employment Type') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_employment_type"
                            class="form-select @error('id_employment_type') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Employment Type') }}</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->id_employment_type }}" @selected(old('id_employment_type', $contract->id_employment_type) === $type->id_employment_type)>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('id_employment_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Staff Position --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Staff Position') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_staff_position"
                            class="form-select @error('id_staff_position') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Staff Position') }}</option>
                            @foreach ($staffPositions as $position)
                                <option value="{{ $position->id_staff_position }}" @selected(old('id_staff_position', $contract->id_staff_position) === $position->id_staff_position)>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('id_staff_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Catatan') }}</label>

                        <textarea name="notes" rows="3"
                            class="form-textarea @error('notes') form-danger @enderror">{{ old('notes', $contract->notes) }}</textarea>

                        @error('notes')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div>

                    {{-- Tanggal Mulai --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Mulai') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="date" name="start_date"
                            value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}"
                            class="form-input @error('start_date') form-danger @enderror" required>

                        @error('start_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tanggal Berakhir --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Berakhir') }}</label>

                        <input type="date" name="end_date"
                            value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}"
                            class="form-input @error('end_date') form-danger @enderror">

                        @error('end_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Gaji -- BEDA dari form create: field ini menyimpan nilai LAMA
                         yang harus tetap tersamar (bukan disembunyikan) kalau tidak
                         berwenang, supaya submit tidak diam-diam menghapusnya
                         (issue12.md Bagian 2e/Tahap 15d). --}}
                    @if ($canViewSalary)
                        <div class="form-group">
                            <label class="form-label">{{ __('Gaji') }}</label>

                            <input type="number" name="salary" value="{{ old('salary', $contract->salary) }}"
                                step="1000" min="0" class="form-input @error('salary') form-danger @enderror">

                            @error('salary')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @else
                        <div class="form-group">
                            <label class="form-label">{{ __('Gaji') }}</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">*****</p>
                            <p class="mt-1 text-xs text-gray-400">{{ __('Anda tidak memiliki akses untuk melihat atau mengubah gaji staff ini.') }}</p>
                        </div>
                    @endif

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a href="{{ route('staff.show', $staff) }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Kontrak') }}
                </button>

            </div>

        </form>

    </div>

@endsection
