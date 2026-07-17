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
                        {{ $player->primary_position ?? 'Player' }}
                    </p>
                </div>
            </div>


            <div class="card-actions flex items-center gap-2">

                <a href="{{ route('players.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                @can('player.update')
                    <a href="{{ route('players.edit', $player->id_player) }}" class="btn btn-primary">
                        Edit Player
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

                            Profil Pemain

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='physical'"
                            :class="tab === 'physical' ? 'tab tab-active' : 'tab'">

                            Fisik & Posisi

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='parent'"
                            :class="tab === 'parent' ? 'tab tab-active' : 'tab'">

                            Parent Information

                        </button>


                        <button type="button" class="focus:outline-none" @click="tab='dokumen'"
                            :class="tab === 'dokumen' ? 'tab tab-active' : 'tab'">

                            Dokumen / File

                        </button>

                    </div>


                    <div x-show="tab==='profile'" x-cloak class="tab-panel">

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Player Code
                                </span>
                                <span class="table-text">
                                    {{ $player->player_code }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Nama Lengkap
                                </span>
                                <span class="table-text">
                                    {{ $player->name }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Nickname
                                </span>
                                <span class="table-text">
                                    {{ $player->nick_name ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Gender
                                </span>
                                <span class="table-text">
                                    {{ $player->gender ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Nationality
                                </span>
                                <span class="table-text">
                                    {{ $player->nationality ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Tanggal Lahir
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
                                    Tinggi Badan
                                </span>
                                <span class="table-text">
                                    {{ $player->height ? $player->height . ' cm' : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Berat Badan
                                </span>
                                <span class="table-text">
                                    {{ $player->weight ? $player->weight . ' kg' : '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Kaki Dominan
                                </span>
                                <span class="table-text">
                                    {{ $player->preferred_foot ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Posisi Utama
                                </span>
                                <span class="table-text">
                                    {{ $player->primary_position ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Posisi Kedua
                                </span>
                                <span class="table-text">
                                    {{ $player->secondary_position ?? '-' }}
                                </span>
                            </div>

                            <div>
                                <span class="block mb-1 text-xs text-gray-400">
                                    Tanggal Bergabung
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
                                Informasi orang tua akan tersedia pada pengembangan berikutnya.
                            </p>

                        </div>

                    </div>


                    <div x-show="tab==='dokumen'" x-cloak class="tab-panel">

                        <div class="rounded-lg border border-dashed border-gray-200 p-5 dark:border-gray-800">

                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Dokumen pemain belum tersedia.
                            </p>

                        </div>

                    </div>


                </div>


                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        Catatan Pemain
                    </h4>

                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ $player->notes ?: 'Tidak ada catatan pemain.' }}
                    </p>

                </div>


            </div>


            <div class="space-y-6">


                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        Informasi Academy
                    </h4>

                    <div class="mt-4 space-y-4">

                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                Academy
                            </span>

                            <span class="table-text">
                                {{ $player->academy->name ?? '-' }}
                            </span>
                        </div>


                        <div>
                            <span class="mb-1 block text-xs text-gray-400">
                                Type Player
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
                                Status Player
                            </span>

                            @if ($player->user->status)
                                <span class="badge badge-success">
                                    Active
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    Disabled
                                </span>
                            @endif

                        </div>

                    </div>

                </div>



                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title border-b border-gray-100 pb-3 dark:border-gray-800">
                        Informasi Account
                    </h4>


                    <div class="mt-4 space-y-4">

                        @if ($player->user)

                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    Nama Account
                                </span>

                                <span class="table-text">
                                    {{ $player->user->name }}
                                </span>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    Email
                                </span>

                                <a href="mailto:{{ $player->user->email }}"
                                    class="link-primary break-all text-sm font-medium">

                                    {{ $player->user->email }}

                                </a>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    Role
                                </span>

                                <span class="badge badge-secondary">
                                    {{ $player->user->roles->first()->name ?? '-' }}
                                </span>
                            </div>


                            <div>
                                <span class="mb-1 block text-xs text-gray-400">
                                    Status Account
                                </span>

                                @if ($player->user->status)
                                    <span class="badge badge-success">
                                        Aktif
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        Nonaktif
                                    </span>
                                @endif

                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Player belum memiliki akun.
                            </p>

                            @can('user.create')
                                <a href="{{ route('players.account.create', $player) }}"
                                    class="btn btn-primary w-full">

                                    Buat Account

                                </a>
                            @endcan

                        @endif

                    </div>

                </div>



                <div class="rounded-xl border border-gray-100 p-5 dark:border-gray-800">

                    <h4 class="section-title">
                        Informasi Sistem
                    </h4>


                    <div class="mt-4 space-y-3 text-xs text-gray-400">

                        <div class="flex items-center justify-between">

                            <span>
                                Dibuat pada
                            </span>

                            <span class="font-medium text-gray-600 dark:text-gray-300">
                                {{ $player->created_at?->format('d M Y, H:i') }}
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <span>
                                Pembaruan terakhir
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
