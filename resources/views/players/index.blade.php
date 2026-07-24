@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />

    <!-- Alerts -->
    <x-alert />
    <!-- Alerts End -->

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Player List') }}</h3>
                <p class="card-description">{{ __('Manajemen data pemain akademi sepak bola.') }}</p>
            </div>

            @can('player.create')
                <div class="card-actions">
                    <a href="{{ route('players.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        {{ __('Tambah Player') }}
                    </a>
                </div>
            @endcan
        </div>

        @php
            $allCount = array_sum($statusCounts);
            $hasActiveFilters = !empty($filters);

            // Saran kategori in-memory (TIDAK query DB per baris, issue17.md
            // Aturan Emas) -- dipakai buat badge "saran kategori" kalau beda
            // dari yang tersimpan.
            $suggestedCategoryFor = function ($player) use ($playerCategoryOptions) {

                $academyCategories = $playerCategoryOptions->where('id_academy', $player->id_academy);

                return app(\App\Services\PlayerCategoryService::class)
                    ->suggestFromCollection($academyCategories, $player->birth_date);
            };
        @endphp

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            <x-table.tabs route="players.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $allCount],
                'active' => ['label' => __('Aktif'), 'count' => $statusCounts['active']],
                'inactive' => ['label' => __('Nonaktif'), 'count' => $statusCounts['inactive']],
                'graduated' => ['label' => __('Lulus'), 'count' => $statusCounts['graduated']],
                'left' => ['label' => __('Keluar'), 'count' => $statusCounts['left']],
            ]" />
        </div>

        <x-table.toolbar route="players.index" :filters="$filters" :placeholder="__('Cari nama, nickname, atau kode player...')">

            <div class="form-group">
                <label class="form-label">{{ __('Urutkan') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('Terbaru') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Terlama') }}</option>
                    <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>{{ __('Nama A-Z') }}</option>
                    <option value="name_desc" @selected(($filters['sort'] ?? '') === 'name_desc')>{{ __('Nama Z-A') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Player Type') }}</label>
                <select name="id_player_type" class="form-select">
                    <option value="">{{ __('Semua Type') }}</option>
                    @foreach ($playerTypeOptions as $type)
                        <option value="{{ $type->id_player_type }}" @selected(($filters['id_player_type'] ?? '') === $type->id_player_type)>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Kategori Umur') }}</label>
                <select name="id_player_category" class="form-select">
                    <option value="">{{ __('Semua Kategori') }}</option>
                    @foreach ($playerCategoryOptions as $category)
                        <option value="{{ $category->id_player_category }}" @selected(($filters['id_player_category'] ?? '') === $category->id_player_category)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Gender') }}</label>
                <select name="gender" class="form-select">
                    <option value="">{{ __('Semua Gender') }}</option>
                    <option value="male" @selected(($filters['gender'] ?? '') === 'male')>Male</option>
                    <option value="female" @selected(($filters['gender'] ?? '') === 'female')>Female</option>
                </select>
            </div>

        </x-table.toolbar>

        <div class="table-wrapper">
            <table class="table">
                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Info Player') }}</th>
                        <th class="table-header-cell">{{ __('Profil') }}</th>
                        <th class="table-header-cell">{{ __('Posisi') }}</th>
                        <th class="table-header-cell">{{ __('Type') }}</th>
                        <th class="table-header-cell">{{ __('Kategori') }}</th>
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    @forelse ($players as $player)
                        <tr class="table-row">
                            <td class="table-cell">
                                <div class="flex items-center gap-3">
                                    <div class="table-avatar">
                                        @if ($player->photo && Storage::disk('public')->exists($player->photo))
                                            <img src="{{ asset('storage/' . $player->photo) }}" alt="{{ $player->name }}"
                                                class="h-full w-full object-cover">
                                        @else
                                            <span class="avatar-placeholder">
                                                {{ strtoupper(substr($player->name ?? 'P', 0, 2)) }}
                                            </span>
                                        @endif

                                    </div>
                                    <div>
                                        <a href="{{ route('players.show', $player->id_player) }}" class="table-title">
                                            @if ($player->nick_name)
                                                {{ $player->nick_name }}
                                            @else
                                                {{ $player->name }}
                                            @endif
                                        </a>
                                        <span class="table-subtitle">
                                            {{ $player->name }} <br>
                                            {{ $player->player_code }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="table-cell">
                                <span class="table-text">
                                    {{ $player->birth_date ? \Carbon\Carbon::parse($player->birth_date)->format('d M Y') : '-' }}
                                </span>
                                <span class="table-subtitle">
                                    {{ ucfirst($player->gender ?? '-') }}

                                    @if ($player->nationality)
                                        - {{ $player->nationality }}
                                    @endif
                                </span>
                            </td>
                            <td class="table-cell">
                                @if ($player->primaryPosition)
                                    <span class="badge badge-primary">{{ $player->primaryPosition->code }}</span>
                                @else
                                    <span class="table-subtitle">-</span>
                                @endif

                                @if ($player->secondaryPosition)
                                    <span class="badge badge-secondary">{{ $player->secondaryPosition->code }}</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if ($player->playerType)
                                    <span
                                        class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }}">
                                        {{ $player->playerType->name }}
                                    </span>
                                @else
                                    <span class="table-subtitle">-</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if ($player->playerCategory)
                                    <span class="badge badge-secondary">{{ $player->playerCategory->name }}</span>

                                    @php $suggested = $suggestedCategoryFor($player); @endphp
                                    @if ($suggested && $suggested->id_player_category !== $player->id_player_category)
                                        <span class="table-subtitle" title="{{ __('Saran berdasarkan umur saat ini') }}">
                                            ⚠ {{ __('Saran') }}: {{ $suggested->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="table-subtitle">-</span>
                                @endif
                            </td>
                            <td class="table-cell">
                                @if ($player->status)
                                    <span class="badge badge-success">
                                        {{ __('Aktif') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        {{ __('Nonaktif') }}
                                    </span>
                                @endif
                            </td>
                            <td class="table-cell text-right">
                                <div class="table-action">
                                    {{-- Detail --}}
                                    @can('player.view')
                                        <a href="{{ route('players.show', $player->id_player) }}"
                                            class="btn-icon btn-icon-primary" title="{{ __('Detail') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">

                                                <path
                                                    d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                                <path
                                                    d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>
                                    @endcan

                                    {{-- Edit --}}
                                    @can('player.update')
                                        <a href="{{ route('players.edit', $player->id_player) }}"
                                            class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">

                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                            </svg>
                                        </a>
                                    @endcan

                                    {{-- Create Account --}}
                                    @can('user.create')
                                        @if (!$player->id_user)
                                            <a href="{{ route('players.account.create', $player->id_player) }}"
                                                class="btn-icon btn-icon-success" title="{{ __('Buat Akun') }}">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">

                                                    <path
                                                        d="M10 10C12.0711 10 13.75 8.32107 13.75 6.25C13.75 4.17893 12.0711 2.5 10 2.5C7.92893 2.5 6.25 4.17893 6.25 6.25C6.25 8.32107 7.92893 10 10 10Z"
                                                        stroke="currentColor" stroke-width="1.5" />

                                                    <path
                                                        d="M3.75 17.5C3.75 14.7386 6.54822 12.5 10 12.5C13.4518 12.5 16.25 14.7386 16.25 17.5"
                                                        stroke="currentColor" stroke-width="1.5" />
                                                </svg>
                                            </a>
                                        @endif
                                    @endcan

                                    {{-- Delete --}}
                                    @can('player.delete')
                                        <x-button.delete :action="route('players.destroy', $player->id_player)" :name="$player->name" />
                                    @endcan
                                </div>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="text-gray-300 dark:text-gray-700 mb-3">

                                        <path
                                            d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                    @if ($hasActiveFilters)
                                        <h4 class="empty-title">
                                            {{ __('Tidak ada player yang cocok') }}
                                        </h4>
                                        <p class="empty-description">
                                            {{ __('Coba ubah kata kunci atau filter yang dipakai') }}
                                        </p>
                                        <a href="{{ route('players.index') }}" class="empty-link">
                                            {{ __('Reset Filter') }}
                                        </a>
                                    @else
                                        <h4 class="empty-title">
                                            {{ __('Belum ada data Player') }}
                                        </h4>
                                        <p class="empty-description">
                                            {{ __('Tambah player sekarang') }}
                                        </p>
                                        @can('player.create')
                                            <a href="{{ route('players.create') }}" class="empty-link">
                                                {{ __('Tambah sekarang') }}
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($players as $player)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="table-avatar shrink-0">
                                @if ($player->photo && Storage::disk('public')->exists($player->photo))
                                    <img src="{{ asset('storage/' . $player->photo) }}" alt="{{ $player->name }}"
                                        class="h-full w-full object-cover">
                                @else
                                    <span class="avatar-placeholder">
                                        {{ strtoupper(substr($player->name ?? 'P', 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('players.show', $player->id_player) }}"
                                    class="table-title truncate">
                                    @if ($player->nick_name)
                                        {{ $player->nick_name }}
                                    @else
                                        {{ $player->name }}
                                    @endif
                                </a>
                                <span class="table-subtitle truncate">
                                    {{ $player->name }} &middot; {{ $player->player_code }}
                                </span>
                            </div>
                        </div>

                        @if ($player->status)
                            <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge badge-danger shrink-0">{{ __('Nonaktif') }}</span>
                        @endif
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Profil') }}</span>
                            <span class="table-text">
                                {{ $player->birth_date ? \Carbon\Carbon::parse($player->birth_date)->format('d M Y') : '-' }}
                            </span>
                            <span class="table-subtitle">
                                {{ ucfirst($player->gender ?? '-') }}
                                @if ($player->nationality)
                                    - {{ $player->nationality }}
                                @endif
                            </span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Posisi') }}</span>
                            <div class="flex flex-wrap gap-1">
                                @if ($player->primaryPosition)
                                    <span class="badge badge-primary">{{ $player->primaryPosition->code }}</span>
                                @else
                                    <span class="table-subtitle">-</span>
                                @endif

                                @if ($player->secondaryPosition)
                                    <span class="badge badge-secondary">{{ $player->secondaryPosition->code }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Type') }}</span>
                            @if ($player->playerType)
                                <span
                                    class="badge {{ $player->playerType->is_billable ? 'badge-primary' : 'badge-secondary' }} w-fit">
                                    {{ $player->playerType->name }}
                                </span>
                            @else
                                <span class="table-subtitle">-</span>
                            @endif
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Kategori') }}</span>
                            @if ($player->playerCategory)
                                <span class="badge badge-secondary w-fit">{{ $player->playerCategory->name }}</span>

                                @php $suggested = $suggestedCategoryFor($player); @endphp
                                @if ($suggested && $suggested->id_player_category !== $player->id_player_category)
                                    <span class="table-subtitle" title="{{ __('Saran berdasarkan umur saat ini') }}">
                                        ⚠ {{ __('Saran') }}: {{ $suggested->name }}
                                    </span>
                                @endif
                            @else
                                <span class="table-subtitle">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-card-actions">
                        {{-- Detail --}}
                        @can('player.view')
                            <a href="{{ route('players.show', $player->id_player) }}"
                                class="btn-icon btn-icon-primary" title="{{ __('Detail') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path
                                        d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <path
                                        d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        {{-- Edit --}}
                        @can('player.update')
                            <a href="{{ route('players.edit', $player->id_player) }}"
                                class="btn-icon btn-icon-warning" title="{{ __('Edit') }}">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z" stroke="currentColor"
                                        stroke-width="1.5" />
                                </svg>
                            </a>
                        @endcan

                        {{-- Create Account --}}
                        @can('user.create')
                            @if (!$player->id_user)
                                <a href="{{ route('players.account.create', $player->id_player) }}"
                                    class="btn-icon btn-icon-success" title="{{ __('Buat Akun') }}">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path
                                            d="M10 10C12.0711 10 13.75 8.32107 13.75 6.25C13.75 4.17893 12.0711 2.5 10 2.5C7.92893 2.5 6.25 4.17893 6.25 6.25C6.25 8.32107 7.92893 10 10 10Z"
                                            stroke="currentColor" stroke-width="1.5" />
                                        <path
                                            d="M3.75 17.5C3.75 14.7386 6.54822 12.5 10 12.5C13.4518 12.5 16.25 14.7386 16.25 17.5"
                                            stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                </a>
                            @endif
                        @endcan

                        {{-- Delete --}}
                        @can('player.delete')
                            <x-button.delete :action="route('players.destroy', $player->id_player)" :name="$player->name" />
                        @endcan
                    </div>
                </div>

            @empty

                <div class="table-card">
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="text-gray-300 dark:text-gray-700 mb-3">
                            <path
                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        @if ($hasActiveFilters)
                            <h4 class="empty-title">
                                {{ __('Tidak ada player yang cocok') }}
                            </h4>
                            <p class="empty-description">
                                {{ __('Coba ubah kata kunci atau filter yang dipakai') }}
                            </p>
                            <a href="{{ route('players.index') }}" class="empty-link">
                                {{ __('Reset Filter') }}
                            </a>
                        @else
                            <h4 class="empty-title">
                                {{ __('Belum ada data Player') }}
                            </h4>
                            <p class="empty-description">
                                {{ __('Tambah player sekarang') }}
                            </p>
                            @can('player.create')
                                <a href="{{ route('players.create') }}" class="empty-link">
                                    {{ __('Tambah sekarang') }}
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if ($players->hasPages())
            <div class="table-footer">
                {{ $players->withQueryString()->links() }}
            </div>
        @endif

    </div>

    {{-- Delete Confirmation Modal --}}
    <x-modal.delete />

@endsection
