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
                        {{ $staff->activeContract?->position?->name ?? __('Staff') }}
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

                        <button type="button" class="focus:outline-none" @click="tab='contracts'"
                            :class="tab === 'contracts' ? 'tab tab-active' : 'tab'">
                            {{ __('Riwayat Kontrak') }}
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

                        @if ($staff->activeContract)
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Employment Type') }}</span>
                                    <span class="table-text">{{ $staff->activeContract->employmentType->name ?? '-' }}</span>
                                </div>

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Staff Position') }}</span>
                                    <span class="table-text">{{ $staff->activeContract->position->name ?? '-' }}</span>
                                </div>

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Tanggal Bergabung') }}</span>
                                    <span class="table-text">{{ $staff->activeContract->start_date?->format('d M Y') ?? '-' }}</span>
                                </div>

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Tanggal Keluar') }}</span>
                                    <span class="table-text">{{ $staff->activeContract->end_date?->format('d M Y') ?? '-' }}</span>
                                </div>

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Gaji') }}</span>
                                    <span class="table-text">
                                        <x-salary-amount :staff="$staff" :amount="$staff->activeContract->salary" />
                                    </span>
                                </div>

                                <div>
                                    <span class="block mb-1 text-xs text-gray-400">{{ __('Status Kepegawaian') }}</span>
                                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                                </div>

                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Staff ini belum punya kontrak aktif.') }}
                            </p>
                        @endif

                    </div>

                    <div x-show="tab==='contracts'" x-cloak class="tab-panel">

                        <div class="mb-4 flex justify-end">
                            @can('staff.update')
                                <a href="{{ route('staff.contracts.create', $staff) }}" class="btn btn-primary btn-sm">
                                    {{ __('Buat Kontrak Baru') }}
                                </a>
                            @endcan
                        </div>

                        <div class="space-y-3">
                            @forelse ($staff->contracts as $contract)
                                <div class="rounded-lg border border-gray-100 p-4 dark:border-gray-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="table-title">{{ $contract->contract_code }}</span>
                                            <span class="table-subtitle">
                                                {{ $contract->position->name ?? '-' }} &middot; {{ $contract->employmentType->name ?? '-' }}
                                            </span>
                                        </div>

                                        @php
                                            $contractStatusBadge = match ($contract->status) {
                                                'draft' => ['label' => __('Draft'), 'class' => 'badge-secondary'],
                                                'active' => ['label' => __('Active'), 'class' => 'badge-success'],
                                                'completed' => ['label' => __('Completed'), 'class' => 'badge-primary'],
                                                'terminated' => ['label' => __('Terminated'), 'class' => 'badge-danger'],
                                                'cancelled' => ['label' => __('Cancelled'), 'class' => 'badge-secondary'],
                                            };
                                        @endphp
                                        <span class="badge {{ $contractStatusBadge['class'] }}">{{ $contractStatusBadge['label'] }}</span>
                                    </div>

                                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-400 md:grid-cols-4">
                                        <span>{{ __('Mulai') }}: {{ $contract->start_date?->format('d M Y') }}</span>
                                        <span>{{ __('Berakhir') }}: {{ $contract->end_date?->format('d M Y') ?? '-' }}</span>
                                        <span>{{ __('Gaji') }}: <x-salary-amount :staff="$staff" :amount="$contract->salary" /></span>
                                    </div>

                                    {{-- Aksi transisi -- form polos + confirm() bawaan browser,
                                         boleh diselaraskan ke pola x-modal/$dispatch kalau mau
                                         konsisten dengan interaksi lain (issue12.md Tahap 11d). --}}
                                    <div class="mt-3 flex gap-2">
                                        @can('staff.update')
                                            @if ($contract->status === 'draft')
                                                <a href="{{ route('staff.contracts.edit', [$staff, $contract]) }}" class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                        <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                            stroke="currentColor" stroke-width="1.5" />
                                                    </svg>
                                                </a>

                                                <form action="{{ route('staff.contracts.activate', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Aktifkan kontrak ini?') }}')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-success btn-sm">{{ __('Aktifkan') }}</button>
                                                </form>

                                                <form action="{{ route('staff.contracts.cancel', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Batalkan kontrak Draft ini?') }}')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-secondary btn-sm">{{ __('Batalkan') }}</button>
                                                </form>
                                            @elseif ($contract->status === 'active')
                                                <form action="{{ route('staff.contracts.complete', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Tandai kontrak ini selesai?') }}')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Selesaikan') }}</button>
                                                </form>

                                                <form action="{{ route('staff.contracts.terminate', [$staff, $contract]) }}" method="POST" onsubmit="return confirm('{{ __('Hentikan kontrak ini sebelum waktunya?') }}')">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('Hentikan') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada kontrak.') }}</p>
                            @endforelse
                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        {{ __('Dokumen') }}
                    </h4>

                    <div class="mt-3">
                        <x-document-manager :documentable="$staff" :upload-route="route('staff.documents.store', $staff)"
                            :types="config('faos.document_types.staff')" :can-manage="auth()->user()->can('staff.update')" />
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

                            @if ($staff->activeContract?->employmentType)
                                <span class="badge badge-secondary">{{ $staff->activeContract->employmentType->name }}</span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">{{ __('Staff Position') }}</span>

                            @if ($staff->activeContract?->position)
                                <span class="badge badge-secondary">{{ $staff->activeContract->position->name }}</span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">{{ __('Status Kepegawaian') }}</span>

                            @if ($staff->activeContract)
                                <span class="badge badge-success">{{ __('Aktif') }}</span>
                            @else
                                <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                            @endif
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
