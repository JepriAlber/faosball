@extends('layouts.app', ['page' => 'players'])

@section('title', $player->name . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <!-- Alerts -->
    <x-alert />
    <!-- Alerts End -->
    <div class="card">

        <div class="card-header">

            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    @if ($player->photo)
                        <img src="{{ asset('storage/' . $player->photo) }}" class="h-full w-full object-cover">
                    @else
                        <span class="avatar-placeholder">
                            {{ strtoupper(substr($player->name, 0, 2)) }}
                        </span>
                    @endif
                </div>

                <div>
                    <h3 class="card-title text-xl">
                        {{ $player->name }}
                    </h3>

                    <p class="card-description">
                        {{ $player->primaryPosition->name ?? __('Player') }}
                    </p>
                </div>
            </div>


            <div class="card-actions flex items-center gap-2">

                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    {{ __('Kembali') }}
                </a>

                @can('player.update')
                    <a href="{{ route('players.edit', $player->id_player) }}" class="btn btn-primary">
                        {{ __('Edit Player') }}
                    </a>
                @endcan

                @if ($player->user)
                    @can('user.update')
                        <x-account.dropdown :model="$player" :user="$player->user" route-create="players.account.create"
                            route-edit="players.account.edit" route-password="players.account.password"
                            route-status="players.account.status" />
                    @endcan
                @else
                    @can('user.create')
                        <x-account.dropdown :model="$player" :user="$player->user" route-create="players.account.create"
                            route-edit="players.account.edit" route-password="players.account.password"
                            route-status="players.account.status" />
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

                            {{ __('Profil Pemain') }}

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='physical'"
                            :class="tab === 'physical' ? 'tab tab-active' : 'tab'">

                            {{ __('Fisik & Posisi') }}

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='parent'"
                            :class="tab === 'parent' ? 'tab tab-active' : 'tab'">

                            {{ __('Parent Information') }}

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='dokumen'"
                            :class="tab === 'dokumen' ? 'tab tab-active' : 'tab'">

                            {{ __('Dokumen / File') }}

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='teams'"
                            :class="tab === 'teams' ? 'tab tab-active' : 'tab'">

                            {{ __('Teams') }}

                        </button>

                    </div>


                    <div x-show="tab==='profile'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Player Code') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->player_code }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Nama Lengkap') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->name }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Nickname') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->nick_name ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Gender') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->gender ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Nationality') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->nationality ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Tanggal Lahir') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->birth_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>

                        </div>

                    </div>
                    <div x-show="tab==='physical'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Tinggi Badan') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->height ? $player->height . ' cm' : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Berat Badan') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->weight ? $player->weight . ' kg' : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Kaki Dominan') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->preferred_foot ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Posisi Utama') }}</span>
                                <span class="table-text">
                                    @if ($player->primaryPosition)
                                        {{ $player->primaryPosition->code }} — {{ $player->primaryPosition->name }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">{{ __('Posisi Kedua') }}</span>
                                <span class="table-text">
                                    @if ($player->secondaryPosition)
                                        {{ $player->secondaryPosition->code }} — {{ $player->secondaryPosition->name }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    {{ __('Tanggal Bergabung') }}
                                </span>
                                <span class="table-text">
                                    {{ $player->join_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>

                        </div>

                    </div>


                    <div x-show="tab==='parent'" x-cloak class="tab-panel">

                        <div class="rounded-lg border border-dashed border-gray-200 p-5 dark:border-gray-800">

                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Informasi orang tua akan tersedia pada pengembangan berikutnya.') }}
                            </p>

                        </div>

                    </div>


                    <div x-show="tab==='dokumen'" x-cloak class="tab-panel">

                        <x-document-manager :documentable="$player" :upload-route="route('players.documents.store', $player)"
                            :types="config('faos.document_types.player')" :can-manage="auth()->user()->can('player.update')" />

                    </div>


                    <div x-show="tab==='teams'" x-cloak class="tab-panel">

                        <div class="space-y-3">
                            @forelse ($player->teamPlayers as $teamPlayer)
                                <div class="table-card">
                                    <div class="table-card-header">
                                        <div class="min-w-0">
                                            @can('team.view')
                                                <a href="{{ route('teams.show', $teamPlayer->team) }}" class="table-title truncate">
                                                    {{ $teamPlayer->team->name }}
                                                </a>
                                            @else
                                                <span class="table-title truncate">{{ $teamPlayer->team->name }}</span>
                                            @endcan
                                            <span class="table-subtitle truncate">
                                                {{ $teamPlayer->team->code }} &middot; {{ $teamPlayer->team->season->name }}
                                            </span>
                                        </div>

                                        @if ($teamPlayer->isActive())
                                            <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                                        @endif
                                    </div>

                                    <div class="table-card-body">
                                        <div class="table-card-field">
                                            <span class="table-card-label">{{ __('Nomor Punggung') }}</span>
                                            <span class="table-text">{{ $teamPlayer->jersey_number }}</span>
                                        </div>

                                        <div class="table-card-field">
                                            <span class="table-card-label">{{ __('Captain') }}</span>
                                            <span class="table-text">{{ $teamPlayer->is_captain ? __('Ya') : '-' }}</span>
                                        </div>

                                        <div class="table-card-field">
                                            <span class="table-card-label">{{ __('Bergabung') }}</span>
                                            <span class="table-text">{{ $teamPlayer->join_date->format('d M Y') }}</span>
                                        </div>

                                        @if (! $teamPlayer->isActive())
                                            <div class="table-card-field">
                                                <span class="table-card-label">{{ __('Keluar') }}</span>
                                                <span class="table-text">{{ $teamPlayer->leave_date->format('d M Y') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="table-card">
                                    <div class="empty-state">
                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="mb-3 text-gray-300 dark:text-gray-700">
                                            <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                                        </svg>
                                        <h4 class="empty-title">{{ __('Belum menjadi anggota tim manapun.') }}</h4>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                    </div>


                </div>


                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        {{ __('Catatan Pemain') }}
                    </h4>

                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ $player->notes ?: __('Tidak ada catatan pemain.') }}
                    </p>

                </div>


            </div>


            <div class="space-y-6">


                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Academy') }}
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Academy') }}
                            </span>

                            <span class="table-text">
                                {{ $player->academy->name ?? '-' }}
                            </span>
                        </div>


                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Type Player') }}
                            </span>

                            @if ($player->playerType)
                                <span
                                    class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }}">
                                    {{ $player->playerType->name }}
                                </span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Kategori Umur') }}
                            </span>

                            @if ($player->playerCategory)
                                <span class="badge badge-secondary">
                                    {{ $player->playerCategory->name }}
                                    ({{ $player->playerCategory->min_age }}-{{ $player->playerCategory->max_age }} th)
                                </span>
                            @else
                                <span class="table-text">-</span>
                            @endif
                        </div>

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                {{ __('Status Player') }}
                            </span>

                            @php
                                $playerStatusBadge = match ($player->status) {
                                    'active' => ['label' => __('Aktif'), 'class' => 'badge-success'],
                                    'inactive' => ['label' => __('Nonaktif'), 'class' => 'badge-danger'],
                                    'graduated' => ['label' => __('Lulus'), 'class' => 'badge-primary'],
                                    'left' => ['label' => __('Keluar'), 'class' => 'badge-secondary'],
                                    default => ['label' => '-', 'class' => 'badge-secondary'],
                                };
                            @endphp

                            <span class="badge {{ $playerStatusBadge['class'] }}">
                                {{ $playerStatusBadge['label'] }}
                            </span>

                        </div>

                    </div>

                </div>



                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        {{ __('Informasi Account') }}
                    </h4>


                    <div class="mt-4 space-y-4">

                        @if ($player->user)

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    {{ __('Nama Account') }}
                                </span>

                                <span class="table-text">
                                    {{ $player->user->name }}
                                </span>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    {{ __('Email') }}
                                </span>

                                <a href="mailto:{{ $player->user->email }}"
                                    class="link-primary break-all text-sm font-medium">

                                    {{ $player->user->email }}

                                </a>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    {{ __('Role') }}
                                </span>

                                <span class="badge badge-secondary">
                                    {{ $player->user->roles->first()->name ?? '-' }}
                                </span>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    {{ __('Status Account') }}
                                </span>

                                @if ($player->user->status)
                                    <span class="badge badge-success">
                                        {{ __('Aktif') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        {{ __('Nonaktif') }}
                                    </span>
                                @endif

                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Player belum memiliki akun.') }}
                            </p>

                            @can('user.create')
                                <a href="{{ route('players.account.create', $player) }}"
                                    class="btn btn-primary w-full">

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

                            <span>
                                {{ __('Dibuat pada') }}
                            </span>

                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $player->created_at?->format('d M Y, H:i') }}
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <span>
                                {{ __('Pembaruan terakhir') }}
                            </span>

                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $player->updated_at?->format('d M Y, H:i') }}
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
