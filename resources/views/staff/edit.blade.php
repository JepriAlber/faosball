@extends('layouts.app', ['page' => 'staff'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Informasi Profil Staff') }}</h3>
                <p class="card-description">{{ __('Perbarui detail informasi staff.') }}</p>
            </div>

            <div class="card-actions">
                <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                @if ($staff->id_user)
                    @can('user.update')
                        <x-account.dropdown :model="$staff" :user="$staff->user" route-create="staff.account.create"
                            route-edit="staff.account.edit" route-password="staff.account.password"
                            route-status="staff.account.status" />
                    @endcan
                @else
                    @can('user.create')
                        <x-account.dropdown :model="$staff" :user="$staff->user" route-create="staff.account.create"
                            route-edit="staff.account.edit" route-password="staff.account.password"
                            route-status="staff.account.status" />
                    @endcan
                @endif
            </div>
        </div>

        <form action="{{ route('staff.update', $staff) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-row">

                {{-- LEFT COLUMN: Scope, Identitas, Kontak --}}
                <div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Staff Code') }}</label>

                        <p class="form-input bg-gray-50 dark:bg-gray-800">{{ $staff->staff_code }}</p>
                    </div>

                    @if ($isSuperAdmin)
                        <div class="form-group">
                            <label class="form-label">{{ __('Academy') }}</label>
                            <p class="form-input bg-gray-50 dark:bg-gray-800">
                                {{ $staff->academy->name ?? '-' }}
                            </p>
                        </div>
                    @endif

                    {{-- Nama Lengkap --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Nama Lengkap') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="full_name" value="{{ old('full_name', $staff->full_name) }}"
                            class="form-input @error('full_name') form-danger @enderror" required>

                        @error('full_name')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Nickname --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>

                        <input type="text" name="nickname" value="{{ old('nickname', $staff->nickname) }}"
                            class="form-input @error('nickname') form-danger @enderror">

                        @error('nickname')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Telepon --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Telepon') }} <span class="text-error-500">*</span>
                        </label>

                        <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}"
                            class="form-input @error('phone') form-danger @enderror" required>

                        @error('phone')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>

                        <input type="email" name="email" value="{{ old('email', $staff->email) }}"
                            class="form-input @error('email') form-danger @enderror">

                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Alamat --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Alamat') }}</label>

                        <textarea name="address" rows="3"
                            class="form-textarea @error('address') form-danger @enderror">{{ old('address', $staff->address) }}</textarea>

                        @error('address')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kota & Provinsi --}}
                    <div class="form-row grid-cols-2">

                        <div class="form-group">
                            <label class="form-label">{{ __('Kota') }}</label>

                            <input type="text" name="city" value="{{ old('city', $staff->city) }}" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">{{ __('Provinsi') }}</label>

                            <input type="text" name="province" value="{{ old('province', $staff->province) }}"
                                class="form-input">
                        </div>

                    </div>

                    {{-- Kode Pos --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Kode Pos') }}</label>

                        <input type="text" name="postal_code" value="{{ old('postal_code', $staff->postal_code) }}"
                            class="form-input">
                    </div>

                </div>

                {{-- RIGHT COLUMN: Deskriptif, Media, Ringkasan Kontrak --}}
                <div>

                    {{-- Gender --}}
                    <div class="form-group">
                        <label class="form-label">
                            {{ __('Jenis Kelamin') }} <span class="text-error-500">*</span>
                        </label>

                        <select name="gender" class="form-select @error('gender') form-danger @enderror" required>
                            <option value="">{{ __('Pilih Gender') }}</option>
                            <option value="male" @selected(old('gender', $staff->gender) === 'male')>{{ __('Laki-laki') }}</option>
                            <option value="female" @selected(old('gender', $staff->gender) === 'female')>{{ __('Perempuan') }}</option>
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

                        <input type="text" name="birth_place" value="{{ old('birth_place', $staff->birth_place) }}"
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

                        <input type="date" name="birth_date"
                            value="{{ old('birth_date', $staff->birth_date?->format('Y-m-d')) }}"
                            class="form-input @error('birth_date') form-danger @enderror" required>

                        @error('birth_date')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kewarganegaraan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Kewarganegaraan') }}</label>

                        <input type="text" name="nationality" value="{{ old('nationality', $staff->nationality) }}"
                            class="form-input">
                    </div>

                    {{-- Agama --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Agama') }}</label>

                        <select name="religion" class="form-select @error('religion') form-danger @enderror">
                            <option value="">{{ __('Pilih Agama') }}</option>
                            <option value="islam" @selected(old('religion', $staff->religion) === 'islam')>{{ __('Islam') }}</option>
                            <option value="kristen" @selected(old('religion', $staff->religion) === 'kristen')>{{ __('Kristen') }}</option>
                            <option value="katolik" @selected(old('religion', $staff->religion) === 'katolik')>{{ __('Katolik') }}</option>
                            <option value="hindu" @selected(old('religion', $staff->religion) === 'hindu')>{{ __('Hindu') }}</option>
                            <option value="buddha" @selected(old('religion', $staff->religion) === 'buddha')>{{ __('Buddha') }}</option>
                            <option value="konghucu" @selected(old('religion', $staff->religion) === 'konghucu')>{{ __('Konghucu') }}</option>
                            <option value="lainnya" @selected(old('religion', $staff->religion) === 'lainnya')>{{ __('Lainnya') }}</option>
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
                            <option value="A" @selected(old('blood_type', $staff->blood_type) === 'A')>A</option>
                            <option value="B" @selected(old('blood_type', $staff->blood_type) === 'B')>B</option>
                            <option value="AB" @selected(old('blood_type', $staff->blood_type) === 'AB')>AB</option>
                            <option value="O" @selected(old('blood_type', $staff->blood_type) === 'O')>O</option>
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
                            <option value="single" @selected(old('marital_status', $staff->marital_status) === 'single')>{{ __('Belum Menikah') }}</option>
                            <option value="married" @selected(old('marital_status', $staff->marital_status) === 'married')>{{ __('Menikah') }}</option>
                            <option value="divorced" @selected(old('marital_status', $staff->marital_status) === 'divorced')>{{ __('Cerai') }}</option>
                            <option value="widowed" @selected(old('marital_status', $staff->marital_status) === 'widowed')>{{ __('Janda/Duda') }}</option>
                        </select>

                        @error('marital_status')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div class="form-group">
                        <label class="form-label">{{ __('Catatan') }}</label>

                        <textarea name="notes" rows="3"
                            class="form-textarea @error('notes') form-danger @enderror">{{ old('notes', $staff->notes) }}</textarea>

                        @error('notes')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Foto --}}
                    <x-staff-photo-field :current-photo-url="$staff->photo ? asset('storage/' . $staff->photo) : null" />

                    {{-- Ringkasan Contract Active (read-only) -- perubahan employment
                         (posisi/gaji/kontrak baru) dikelola lewat halaman Detail Staff,
                         bukan form ini (issue12.md Bagian 2b). --}}
                    <div class="rounded-xl border border-gray-100 p-4 dark:border-gray-800">
                        <h4 class="section-title mb-3">{{ __('Kontrak Aktif Saat Ini') }}</h4>

                        @if ($staff->activeContract)
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ __('Staff Position') }}</span>
                                    <span class="font-medium">{{ $staff->activeContract->position->name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ __('Employment Type') }}</span>
                                    <span class="font-medium">{{ $staff->activeContract->employmentType->name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ __('Gaji') }}</span>
                                    <span class="font-medium"><x-salary-amount :staff="$staff" :amount="$staff->activeContract->salary" /></span>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-gray-400">
                                {{ __('Untuk mengubah posisi, gaji, atau membuat kontrak baru, kelola lewat halaman Detail Staff.') }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Staff ini belum punya kontrak aktif.') }}</p>
                        @endif
                    </div>

                </div>

            </div>

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                    {{ __('Batal') }}
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ __('Update Staff') }}
                </button>

            </div>

        </form>

    </div>

    <x-modal.reset-password />
    <x-modal.status />

@endsection
