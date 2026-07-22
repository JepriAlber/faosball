@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Profil Staff') }}</h3>
                <p class="card-description">{{ __('Masukkan detail lengkap untuk mendaftarkan staff baru.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-row">

                {{-- LEFT COLUMN: Scope, Identitas, Klasifikasi, Kontak --}}
                <div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">
                                {{ __('Academy') }} <span class="text-error-500">*</span>
                            </label>

                            <select name="id_academy" class="form-select @error('id_academy') form-danger @enderror" required>
                                <option value="">{{ __('Pilih Academy') }}</option>
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

                    {{-- Nama Lengkap --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Lengkap') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                            class="form-input @error('full_name') form-danger @enderror" required>

                        @error('full_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nickname --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>

                        <input type="text" name="nickname" value="{{ old('nickname') }}"
                            class="form-input @error('nickname') form-danger @enderror">

                        @error('nickname')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Employment Type --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Employment Type') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="id_employment_type"
                            class="form-select @error('id_employment_type') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Employment Type') }}</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->id_employment_type }}" @selected(old('id_employment_type') === $type->id_employment_type)>
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
                                <option value="{{ $position->id_staff_position }}" @selected(old('id_staff_position') === $position->id_staff_position)>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('id_staff_position')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Telepon --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Telepon') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="form-input @error('phone') form-danger @enderror" required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>

                        <input type="email" name="email" value="{{ old('email') }}"
                            class="form-input @error('email') form-danger @enderror">

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Alamat --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Alamat') }}</label>

                        <textarea name="address" rows="3"
                            class="form-textarea @error('address') form-danger @enderror">{{ old('address') }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kota & Provinsi --}}
                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">{{ __('Kota') }}</label>

                            <input type="text" name="city" value="{{ old('city') }}" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Provinsi') }}</label>

                            <input type="text" name="province" value="{{ old('province') }}" class="form-input">
                        </div>

                    </div>

                    {{-- Kode Pos --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Kode Pos') }}</label>

                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="form-input">
                    </div>

                </div>

                {{-- RIGHT COLUMN: Deskriptif, Kepegawaian, Media, Status --}}
                <div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Jenis Kelamin') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="gender" class="form-select @error('gender') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Gender') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('Laki-laki') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('Perempuan') }}</option>
                        </select>

                        @error('gender')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tempat Lahir --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Tempat Lahir') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="birth_place" value="{{ old('birth_place') }}"
                            class="form-input @error('birth_place') form-danger @enderror" required>

                        @error('birth_place')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tanggal Lahir --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Tanggal Lahir') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                            class="form-input @error('birth_date') form-danger @enderror" required>

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kewarganegaraan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Kewarganegaraan') }}</label>

                        <input type="text" name="nationality" value="{{ old('nationality', 'Indonesia') }}"
                            class="form-input">
                    </div>

                    {{-- Agama --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Agama') }}</label>

                        <select name="religion" class="form-select @error('religion') form-danger @enderror">
                            <option value="">{{ __('Pilih Agama') }}</option>
                            <option value="islam" @selected(old('religion') === 'islam')>{{ __('Islam') }}</option>
                            <option value="kristen" @selected(old('religion') === 'kristen')>{{ __('Kristen') }}</option>
                            <option value="katolik" @selected(old('religion') === 'katolik')>{{ __('Katolik') }}</option>
                            <option value="hindu" @selected(old('religion') === 'hindu')>{{ __('Hindu') }}</option>
                            <option value="buddha" @selected(old('religion') === 'buddha')>{{ __('Buddha') }}</option>
                            <option value="konghucu" @selected(old('religion') === 'konghucu')>{{ __('Konghucu') }}</option>
                            <option value="lainnya" @selected(old('religion') === 'lainnya')>{{ __('Lainnya') }}</option>
                        </select>

                        @error('religion')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Golongan Darah --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Golongan Darah') }}</label>

                        <select name="blood_type" class="form-select @error('blood_type') form-danger @enderror">
                            <option value="">{{ __('Pilih Golongan Darah') }}</option>
                            <option value="A" @selected(old('blood_type') === 'A')>A</option>
                            <option value="B" @selected(old('blood_type') === 'B')>B</option>
                            <option value="AB" @selected(old('blood_type') === 'AB')>AB</option>
                            <option value="O" @selected(old('blood_type') === 'O')>O</option>
                        </select>

                        @error('blood_type')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Status Pernikahan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Status Pernikahan') }}</label>

                        <select name="marital_status" class="form-select @error('marital_status') form-danger @enderror">
                            <option value="">{{ __('Pilih Status Pernikahan') }}</option>
                            <option value="single" @selected(old('marital_status') === 'single')>{{ __('Belum Menikah') }}</option>
                            <option value="married" @selected(old('marital_status') === 'married')>{{ __('Menikah') }}</option>
                            <option value="divorced" @selected(old('marital_status') === 'divorced')>{{ __('Cerai') }}</option>
                            <option value="widowed" @selected(old('marital_status') === 'widowed')>{{ __('Janda/Duda') }}</option>
                        </select>

                        @error('marital_status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Tanggal Bergabung --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Tanggal Bergabung') }}</label>

                        <input type="date" name="join_date" value="{{ old('join_date') }}" class="form-input">
                    </div>

                    {{-- Tanggal Keluar --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Tanggal Keluar') }}</label>

                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                            class="form-input @error('end_date') form-danger @enderror">

                        @error('end_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Gaji --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Gaji') }}</label>

                        <input type="number" name="salary" value="{{ old('salary') }}" step="1000" min="0"
                            class="form-input @error('salary') form-danger @enderror">

                        @error('salary')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Catatan') }}</label>

                        <textarea name="notes" rows="3"
                            class="form-textarea @error('notes') form-danger @enderror">{{ old('notes') }}</textarea>

                        @error('notes')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Foto --}}
                    <x-staff-photo-field :current-photo-url="null" />

                    {{-- Status Kepegawaian --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Status Kepegawaian') }}</label>

                        <select name="status" class="form-select @error('status') form-danger @enderror">
                            <option value="active" @selected(old('status', 'active') === 'active')>{{ __('Aktif') }}</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>{{ __('Nonaktif') }}</option>
                            <option value="resigned" @selected(old('status') === 'resigned')>{{ __('Resign') }}</option>
                        </select>

                        @error('status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <button type="reset" class="btn btn-secondary">
                    {{ __('Reset') }}
                </button>

                <button type="submit" class="btn btn-primary">
                    {{ __('Simpan Staff') }}
                </button>

            </div>

        </form>

    </div>

@endsection
