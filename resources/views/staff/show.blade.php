@extends('layouts.app', ['page' => 'staff'])

@section('title', $staff->full_name . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">

            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    @if ($staff->photo)
                        <img src="{{ asset('storage/' . $staff->photo) }}" class="h-full w-full object-cover">
                    @else
                        <span class="avatar-placeholder">
                            {{ strtoupper(substr($staff->full_name, 0, 2)) }}
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="card-title text-xl">
                        {{ $staff->full_name }}
                    </h3>

                    <p class="card-description">
                        {{ $staff->position->name ?? __('Staff') }}
                    </p>
                </div>
            </div>

            <div class="card-actions flex items-center gap-2">

                <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                @can('staff.update')
                    <a href="{{ route('staff.edit', $staff) }}" class="btn btn-primary">
                        {{ __('Edit Staff') }}
                    </a>
                @endcan

                @if ($staff->user)
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

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="min-w-0 space-y-6 lg:col-span-2">

                <div class="overflow-hidden rounded-xl border border-gray-100 p-5 dark:border-gray-800"
                    x-data="{ tab: 'profile' }">

                    <div class="tabs scrollbar-brand">

                        <button type="button" class="focus:outline-none" @click="tab='profile'"
                            :class="tab === 'profile' ? 'tab tab-active' : 'tab'">
                            {{ __('Profil Staff') }}
                        </button>

                        <button type="button" class="focus:outline-none" @click="tab='contact'"
                            :class="tab === 'contact' ? 'tab tab-active' : 'tab'">
                            {{ __('Kontak') }}
                        </button>

                        <button type="button" class="focus:outline-none" @click="tab='employment'"
                            :class="tab === 'employment' ? 'tab tab-active' : 'tab'">
                            {{ __('Kepegawaian') }}
                        </button>

                    </div>

                    <div x-show="tab==='profile'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Staff Code') }}</span>
                                <span class="table-text">{{ $staff->staff_code }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Nama Lengkap') }}</span>
                                <span class="table-text">{{ $staff->full_name }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Nickname') }}</span>
                                <span class="table-text">{{ $staff->nickname ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Jenis Kelamin') }}</span>
                                <span class="table-text">
                                    {{ $staff->gender === 'male' ? __('Laki-laki') : ($staff->gender === 'female' ? __('Perempuan') : '-') }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Tempat Lahir') }}</span>
                                <span class="table-text">{{ $staff->birth_place ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Tanggal Lahir') }}</span>
                                <span class="table-text">{{ $staff->birth_date?->format('d M Y') ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Kewarganegaraan') }}</span>
                                <span class="table-text">{{ $staff->nationality ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Agama') }}</span>
                                <span class="table-text">{{ $staff->religion ? __(ucfirst($staff->religion)) : '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Golongan Darah') }}</span>
                                <span class="table-text">{{ $staff->blood_type ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Status Pernikahan') }}</span>
                                @php
                                    $maritalLabel = match ($staff->marital_status) {
                                        'single' => __('Belum Menikah'),
                                        'married' => __('Menikah'),
                                        'divorced' => __('Cerai'),
                                        'widowed' => __('Janda/Duda'),
                                        default => '-',
                                    };
                                @endphp
                                <span class="table-text">{{ $maritalLabel }}</span>
                            </div>

                        </div>

                    </div>

                    <div x-show="tab==='contact'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Telepon') }}</span>
                                <span class="table-text">{{ $staff->phone }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Email') }}</span>
                                <span class="table-text">{{ $staff->email ?? '-' }}</span>
                            </div>

                            <div class="md:col-span-2">
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Alamat') }}</span>
                                <span class="table-text">{{ $staff->address ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Kota') }}</span>
                                <span class="table-text">{{ $staff->city ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Provinsi') }}</span>
                                <span class="table-text">{{ $staff->province ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Kode Pos') }}</span>
                                <span class="table-text">{{ $staff->postal_code ?? '-' }}</span>
                            </div>

                        </div>

                    </div>

                    <div x-show="tab==='employment'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Employment Type') }}</span>
                                <span class="table-text">{{ $staff->employmentType->name ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Staff Position') }}</span>
                                <span class="table-text">{{ $staff->position->name ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Tanggal Bergabung') }}</span>
                                <span class="table-text">{{ $staff->join_date?->format('d M Y') ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Tanggal Keluar') }}</span>
                                <span class="table-text">{{ $staff->end_date?->format('d M Y') ?? '-' }}</span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Gaji') }}</span>
                                <span class="table-text">
                                    {{ $staff->salary !== null ? 'Rp ' . number_format($staff->salary, 0, ',', '.') : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Status Kepegawaian') }}</span>
                                @php
                                    $showStatusBadge = match ($staff->status) {
                                        'active' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                        'inactive' => ['label' => __('Nonaktif'), 'class' => 'badge-danger'],
                                        'resigned' => ['label' => __('Resign'), 'class' => 'badge-secondary'],
                                        default => ['label' => '-', 'class' => 'badge-secondary'],
                                    };
                                @endphp
                                <span class="badge {{ $showStatusBadge['class'] }}">{{ $showStatusBadge['label'] }}</span>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        {{ __('Catatan') }}
                    </h4>

                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ $staff->notes ?: __('Tidak ada catatan pemain.') }}
                    </p>

                </div>

            </div>

            <div class="space-y-6">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Employment') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">{{ __('Employment Type') }}</span>

                            @if ($staff->employmentType)
                                <span class="badge badge-secondary">{{ $staff->employmentType->name }}</span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">{{ __('Staff Position') }}</span>

                            @if ($staff->position)
                                <span class="badge badge-secondary">{{ $staff->position->name }}</span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">{{ __('Status Kepegawaian') }}</span>

                            @php
                                $sideStatusBadge = match ($staff->status) {
                                    'active' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                    'inactive' => ['label' => __('Nonaktif'), 'class' => 'badge-danger'],
                                    'resigned' => ['label' => __('Resign'), 'class' => 'badge-secondary'],
                                    default => ['label' => '-', 'class' => 'badge-secondary'],
                                };
                            @endphp

                            <span class="badge {{ $sideStatusBadge['class'] }}">{{ $sideStatusBadge['label'] }}</span>
                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Account') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        @if ($staff->user)

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">{{ __('Nama Account') }}</span>
                                <span class="table-text">{{ $staff->user->name }}</span>
                            </div>

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">{{ __('Email') }}</span>
                                <a href="mailto:{{ $staff->user->email }}" class="link-primary break-all text-sm font-medium">
                                    {{ $staff->user->email }}
                                </a>
                            </div>

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">{{ __('Role') }}</span>
                                <span class="badge badge-secondary">
                                    {{ $staff->user->roles->first()->name ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">{{ __('Status Account') }}</span>

                                @if ($staff->user->status)
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Staff belum memiliki akun.') }}
                            </p>

                            @can('user.create')
                                <a href="{{ route('staff.account.create', $staff) }}" class="btn btn-primary w-full">
                                    {{ __('Buat Account') }}
                                </a>
                            @endcan
                        @endif

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        {{ __('Informasi Sistem') }}
                    </h4>

                    <div class="mt-4 space-y-3 text-xs text-gray-400">

                        <div class="flex items-center justify-between">
                            <span>{{ __('Dibuat pada') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $staff->created_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>{{ __('Pembaruan terakhir') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $staff->updated_at?->format('d M Y, H:i') }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <x-modal.reset-password />
    <x-modal.status />

@endsection
