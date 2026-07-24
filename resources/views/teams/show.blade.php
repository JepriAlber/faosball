@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card" x-data="{ tab: 'players' }">

        <div class="card-header">
            <div class="flex items-center gap-4">
                <div class="avatar avatar-lg avatar-square border border-gray-100 dark:border-gray-800">
                    <span class="avatar-placeholder">{{ strtoupper(substr($team->name, 0, 2)) }}</span>
                </div>

                <div>
                    <h3 class="card-title text-xl">{{ $team->name }}</h3>
                    <p class="card-description">
                        {{ $team->code }} &middot; {{ $team->season->name }}
                        @if ($team->academy)
                            &middot; {{ $team->academy->name }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="card-actions">
                <a href="{{ route('teams.index') }}" class="btn btn-secondary">{{ __('Kembali') }}</a>

                @can('team.update')
                    <a href="{{ route('teams.edit', $team) }}" class="btn btn-primary">{{ __('Edit Team') }}</a>
                @endcan
            </div>
        </div>

        {{-- Info strip --}}
        <div class="grid grid-cols-2 gap-4 border-b border-gray-100 p-5 sm:grid-cols-3 lg:grid-cols-6 dark:border-gray-800">
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Player Category') }}</span>
                <span class="badge badge-secondary">{{ $team->playerCategory->name }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Team Type') }}</span>
                <span class="table-text">{{ ucfirst($team->team_type) }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Status') }}</span>
                @if ($team->status)
                    <span class="badge badge-success">{{ __('Aktif') }}</span>
                @else
                    <span class="badge badge-danger">{{ __('Nonaktif') }}</span>
                @endif
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Player Aktif') }}</span>
                <span class="table-text">{{ $teamPlayers->whereNull('leave_date')->count() }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Jumlah Staff Aktif') }}</span>
                <span class="table-text">{{ $teamStaff->whereNull('leave_date')->count() }}</span>
            </div>
            <div>
                <span class="mb-1 block text-xs text-gray-400">{{ __('Dibuat') }}</span>
                <span class="table-text">{{ $team->created_at->format('d M Y') }}</span>
            </div>
        </div>

        <div class="border-b mt-1 border-gray-100 px-5 dark:border-gray-800">
            <div class="tabs scrollbar-brand">
                <button type="button" class="focus:outline-none" @click="tab='players'"
                    :class="tab === 'players' ? 'tab tab-active' : 'tab'">{{ __('Players') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='staff'"
                    :class="tab === 'staff' ? 'tab tab-active' : 'tab'">{{ __('Staff') }}</button>
            </div>
        </div>

        <div class="p-5">

            {{-- Players --}}
            <div x-show="tab==='players'" x-cloak class="tab-panel" x-data="{ showForm: false }">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                            {{ __('Add Player') }}
                        </button>
                    </div>

                    <form x-show="showForm" x-cloak action="{{ route('teams.players.store', $team) }}" method="POST"
                        class="mb-4 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Player') }}</label>
                                <select name="id_player" class="form-select" required>
                                    <option value="">{{ __('Pilih Player') }}</option>
                                    @foreach ($availablePlayers as $player)
                                        <option value="{{ $player->id_player }}">{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Nomor Punggung') }}</label>
                                <input type="number" name="jersey_number" min="1" max="99" class="form-input"
                                    required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Player') }}</th>
                                <th class="table-header-cell">{{ __('Nomor') }}</th>
                                <th class="table-header-cell">{{ __('Captain') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamPlayers as $teamPlayer)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $teamPlayer->player->name }}</td>
                                    <td class="table-cell">{{ $teamPlayer->jersey_number }}</td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->is_captain)
                                            <span class="badge badge-primary">{{ __('Captain') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($teamPlayer->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }}
                                                ({{ $teamPlayer->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($teamPlayer->isActive())
                                                @if (! $teamPlayer->is_captain)
                                                    <x-button.make-captain
                                                        :action="route('teams.players.update', [$team, $teamPlayer])"
                                                        :name="$teamPlayer->player->name"
                                                        :jersey-number="$teamPlayer->jersey_number" />
                                                @endif
                                                <x-button.leave-team
                                                    :action="route('teams.players.leave', [$team, $teamPlayer])"
                                                    :name="$teamPlayer->player->name" />
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-empty">{{ __('Belum ada player di tim ini.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Card List (mobile & tablet) -->
                <div class="table-card-list">
                    @forelse ($teamPlayers as $teamPlayer)
                        <div class="table-card">
                            <div class="table-card-header">
                                <div class="min-w-0">
                                    <span class="table-title truncate">{{ $teamPlayer->player->name }}</span>
                                    <span class="table-subtitle">{{ __('Nomor') }}
                                        {{ $teamPlayer->jersey_number }}</span>
                                </div>

                                @if ($teamPlayer->isActive())
                                    <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                                @endif
                            </div>

                            <div class="table-card-body">
                                <div class="table-card-field">
                                    <span class="table-card-label">{{ __('Captain') }}</span>
                                    <span class="table-text">{{ $teamPlayer->is_captain ? __('Ya') : '-' }}</span>
                                </div>

                                @if (!$teamPlayer->isActive())
                                    <div class="table-card-field">
                                        <span class="table-card-label">{{ __('Keluar') }}</span>
                                        <span class="table-text">{{ $teamPlayer->leave_date->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>

                            @can('team.update')
                                @if ($teamPlayer->isActive())
                                    <div class="table-card-actions">
                                        @if (! $teamPlayer->is_captain)
                                            <x-button.make-captain
                                                :action="route('teams.players.update', [$team, $teamPlayer])"
                                                :name="$teamPlayer->player->name"
                                                :jersey-number="$teamPlayer->jersey_number" />
                                        @endif
                                        <x-button.leave-team
                                            :action="route('teams.players.leave', [$team, $teamPlayer])"
                                            :name="$teamPlayer->player->name" />
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <div class="table-card">
                            <div class="empty-state">
                                <h4 class="empty-title">{{ __('Belum ada player di tim ini.') }}</h4>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Staff --}}
            <div x-show="tab==='staff'" x-cloak class="tab-panel" x-data="{ showForm: false }">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" @click="showForm = !showForm">
                            {{ __('Assign Staff') }}
                        </button>
                    </div>

                    <form x-show="showForm" x-cloak action="{{ route('teams.staff.store', $team) }}" method="POST"
                        class="mb-4 rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Staff') }}</label>
                                <select name="id_staff" class="form-select" required>
                                    <option value="">{{ __('Pilih Staff') }}</option>
                                    @foreach ($availableStaff as $staff)
                                        <option value="{{ $staff->id_staff }}">{{ $staff->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Peran di Tim') }}</label>
                                <select name="id_team_staff_position" class="form-select" required>
                                    <option value="">{{ __('Pilih Peran') }}</option>
                                    @foreach ($teamStaffPositions as $position)
                                        <option value="{{ $position->id_team_staff_position }}">{{ $position->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}"
                                    class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                @endcan

                <div class="table-wrapper">
                    <table class="table">
                        <thead class="table-head">
                            <tr class="table-header-row">
                                <th class="table-header-cell">{{ __('Nama') }}</th>
                                <th class="table-header-cell">{{ __('Peran di Tim') }}</th>
                                <th class="table-header-cell">{{ __('Status') }}</th>
                                <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="table-body">
                            @forelse ($teamStaff as $ts)
                                <tr class="table-row">
                                    <td class="table-cell">{{ $ts->staff->full_name }}</td>
                                    <td class="table-cell">
                                        <span class="badge badge-secondary">{{ $ts->teamStaffPosition->name }}</span>
                                    </td>
                                    <td class="table-cell">
                                        @if ($ts->isActive())
                                            <span class="badge badge-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('Keluar') }}
                                                ({{ $ts->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($ts->isActive())
                                                <x-button.leave-team
                                                    :action="route('teams.staff.leave', [$team, $ts])"
                                                    :name="$ts->staff->full_name" />
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="table-empty">{{ __('Belum ada staff di tim ini.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Card List (mobile & tablet) -->
                <div class="table-card-list">
                    @forelse ($teamStaff as $ts)
                        <div class="table-card">
                            <div class="table-card-header">
                                <div class="min-w-0">
                                    <span class="table-title truncate">{{ $ts->staff->full_name }}</span>
                                    <span class="table-subtitle">{{ $ts->teamStaffPosition->name }}</span>
                                </div>

                                @if ($ts->isActive())
                                    <span class="badge badge-success shrink-0">{{ __('Aktif') }}</span>
                                @else
                                    <span class="badge badge-secondary shrink-0">{{ __('Keluar') }}</span>
                                @endif
                            </div>

                            @if (!$ts->isActive())
                                <div class="table-card-body">
                                    <div class="table-card-field">
                                        <span class="table-card-label">{{ __('Keluar') }}</span>
                                        <span class="table-text">{{ $ts->leave_date->format('d M Y') }}</span>
                                    </div>
                                </div>
                            @endif

                            @can('team.update')
                                @if ($ts->isActive())
                                    <div class="table-card-actions">
                                        <x-button.leave-team
                                            :action="route('teams.staff.leave', [$team, $ts])"
                                            :name="$ts->staff->full_name" />
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @empty
                        <div class="table-card">
                            <div class="empty-state">
                                <h4 class="empty-title">{{ __('Belum ada staff di tim ini.') }}</h4>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <x-modal.leave-team />
    <x-modal.make-captain />

@endsection
