@extends('layouts.app', ['page' => 'teams'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card" x-data="{ tab: 'overview' }">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ $team->name }}</h3>
                <p class="card-description">
                    {{ $team->code }} &middot; {{ $team->playerCategory->name }} &middot; {{ $team->season->name }}
                </p>
            </div>

            @can('team.update')
                <div class="card-actions">
                    <a href="{{ route('teams.edit', $team) }}" class="btn btn-secondary">{{ __('Edit Team') }}</a>
                </div>
            @endcan
        </div>

        <div class="border-b border-gray-100 px-5 dark:border-gray-800">
            <div class="flex gap-2">
                <button type="button" class="focus:outline-none" @click="tab='overview'"
                    :class="tab === 'overview' ? 'tab tab-active' : 'tab'">{{ __('Overview') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='players'"
                    :class="tab === 'players' ? 'tab tab-active' : 'tab'">{{ __('Players') }}</button>

                <button type="button" class="focus:outline-none" @click="tab='staff'"
                    :class="tab === 'staff' ? 'tab tab-active' : 'tab'">{{ __('Staff') }}</button>
            </div>
        </div>

        <div class="p-5">

            {{-- Overview --}}
            <div x-show="tab==='overview'" x-cloak class="tab-panel">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Player Category') }}</span>
                        <span class="badge badge-secondary">{{ $team->playerCategory->name }}</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs text-gray-400">{{ __('Season') }}</span>
                        <span class="table-text">{{ $team->season->name }}</span>
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
                </div>
            </div>

            {{-- Players --}}
            <div x-show="tab==='players'" x-cloak class="tab-panel">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('add-player-form').classList.toggle('hidden')">
                            {{ __('Add Player') }}
                        </button>
                    </div>

                    <form id="add-player-form" action="{{ route('teams.players.store', $team) }}" method="POST"
                        class="mb-4 hidden rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Player') }}</label>
                                <select name="id_player" class="form-select" required>
                                    <option value="">{{ __('Pilih Player') }}</option>
                                    @foreach (\App\Models\Player::where('id_academy', $team->id_academy)->orderBy('name')->get() as $player)
                                        <option value="{{ $player->id_player }}">{{ $player->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Nomor Punggung') }}</label>
                                <input type="number" name="jersey_number" min="1" max="99" class="form-input" required>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
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
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $teamPlayer->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($teamPlayer->isActive())
                                                <form action="{{ route('teams.players.leave', [$team, $teamPlayer]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan player ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="table-empty">{{ __('Belum ada player di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Staff --}}
            <div x-show="tab==='staff'" x-cloak class="tab-panel">

                @can('team.update')
                    <div class="mb-4 flex justify-end">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('assign-staff-form').classList.toggle('hidden')">
                            {{ __('Assign Staff') }}
                        </button>
                    </div>

                    <form id="assign-staff-form" action="{{ route('teams.staff.store', $team) }}" method="POST"
                        class="mb-4 hidden rounded-lg border border-dashed border-gray-200 p-4 dark:border-gray-800">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Staff') }}</label>
                                <select name="id_staff" class="form-select" required>
                                    <option value="">{{ __('Pilih Staff') }}</option>
                                    @foreach (\App\Models\Staff::where('id_academy', $team->id_academy)->orderBy('full_name')->get() as $staff)
                                        <option value="{{ $staff->id_staff }}">{{ $staff->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Peran di Tim') }}</label>
                                <select name="id_team_staff_position" class="form-select" required>
                                    <option value="">{{ __('Pilih Peran') }}</option>
                                    @foreach (\App\Models\TeamStaffPosition::where('id_academy', $team->id_academy)->where('status', true)->orderBy('name')->get() as $position)
                                        <option value="{{ $position->id_team_staff_position }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">{{ __('Tanggal Bergabung') }}</label>
                                <input type="date" name="join_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
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
                                            <span class="badge badge-secondary">{{ __('Keluar') }} ({{ $ts->leave_date->format('d M Y') }})</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        @can('team.update')
                                            @if ($ts->isActive())
                                                <form action="{{ route('teams.staff.leave', [$team, $ts]) }}" method="POST"
                                                    onsubmit="return confirm('{{ __('Keluarkan staff ini dari tim?') }}')" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn-icon btn-icon-danger" title="{{ __('Keluarkan') }}">
                                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                            <path d="M13.3333 14.1667L17.5 10M17.5 10L13.3333 5.83333M17.5 10H7.5M7.5 2.5H5.83333C4.44926 2.5 3.75721 2.5 3.29027 2.89675C2.5 3.5719 2.5 4.72038 2.5 6.66667V13.3333C2.5 15.2796 2.5 16.4281 3.29027 17.1032C3.75721 17.5 4.44926 17.5 5.83333 17.5H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="table-empty">{{ __('Belum ada staff di tim ini.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

@endsection
