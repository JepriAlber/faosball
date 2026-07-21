@extends('layouts.app', ['page' => 'academy'])

@section('title', $academy->name . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <div class="card">

        <div class="card-header">
            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    @if ($academy->logo)
                        <img src="{{ asset('storage/' . $academy->logo) }}" alt="Logo {{ $academy->name }}"
                            class="h-full w-full object-cover">
                    @else
                        <span class="avatar-placeholder">
                            {{ strtoupper(substr($academy->name, 0, 2)) }}
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="card-title text-xl">{{ $academy->name }}</h3>
                    <p class="card-description italic">"{{ $academy->tagline }}"</p>
                </div>
            </div>

            <div class="card-actions">
                <a href="{{ route('academies.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                <a href="{{ route('academies.edit', $academy->id_academy) }}" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ __('Ubah Profile') }}
                </a>

                <x-account.dropdown :model="$academy" :user="$academy->owner" route-create="academies.account.create"
                    route-edit="academies.account.edit" route-password="academies.account.password"
                    route-status="academies.account.status" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="order-2 space-y-6 lg:order-0 lg:col-span-2">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">
                    <h4 class="section-title">{{ __('Deskripsi Academy') }}</h4>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600 whitespace-pre-line dark:text-gray-400">
                        {{ $academy->description ?: __('Tidak ada deskripsi profil untuk akademi ini.') }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">
                    <h4 class="section-title">{{ __('Alamat Lengkap') }}</h4>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600 whitespace-pre-line dark:text-gray-400">
                        {{ $academy->address }}
                    </p>
                </div>

            </div>

            <div class="order-1 space-y-6 lg:order-0">

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Ringkas') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">
                                {{ __('Status Keaktifan') }}
                            </span>

                            @if ($academy->status)
                                <span class="badge badge-success">
                                    {{ __('Aktif') }}
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    {{ __('Nonaktif') }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">
                                {{ __('Email Resmi') }}
                            </span>

                            <a href="mailto:{{ $academy->email }}" class="link-primary text-sm font-medium break-all">
                                {{ $academy->email }}
                            </a>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">
                                {{ __('Nomor Telepon') }}
                            </span>

                            <span class="table-text">
                                {{ $academy->phone }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">
                                {{ __('Slug URL') }}
                            </span>

                            <span class="badge badge-secondary">
                                {{ $academy->slug }}
                            </span>
                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Langganan') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">{{ __('Status') }}</span>

                            @php
                                $subscriptionBadges = [
                                    'aktif' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                    'akan_berakhir' => ['label' => __('Akan Berakhir'), 'class' => 'badge-warning'],
                                    'kadaluarsa' => ['label' => __('Kadaluarsa'), 'class' => 'badge-danger'],
                                    'belum_diatur' => ['label' => __('Belum Diatur'), 'class' => 'badge-secondary'],
                                ];
                                $badge = $subscriptionBadges[$subscriptionStatus];
                            @endphp

                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">{{ __('Tipe Langganan') }}</span>
                            <span class="table-text">
                                {{ $academy->subscription_type ? $subscriptionTypes[$academy->subscription_type] : '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">{{ __('Biaya Langganan') }}</span>
                            <span class="table-text">
                                {{ $academy->subscription_fee ? 'Rp ' . number_format($academy->subscription_fee, 0, ',', '.') : '-' }}
                            </span>
                        </div>

                        <div>
                            <span class="block text-xs text-gray-400 mb-1">{{ __('Periode') }}</span>
                            <span class="table-text">
                                @if ($academy->subscription_started_at && $academy->subscription_ends_at)
                                    {{ $academy->subscription_started_at->format('d M Y') }}
                                    &mdash;
                                    {{ $academy->subscription_ends_at->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>

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
                                {{ $academy->created_at->format('d M Y, H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span>{{ __('Pembaruan terakhir') }}</span>
                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $academy->updated_at->format('d M Y, H:i') }}
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
