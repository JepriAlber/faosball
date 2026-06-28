@extends('layouts.app', ['page' => 'players'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')
    <div x-data="{ showDeleteModal: false, deleteAction: '', playerName: '' }">

        <div x-data="{ pageName: @js($title) }">
            @include('partials.breadcrumb')
        </div>

        @include('partials.alert')

        <div class="card">

            <div class="card-header">
                <div>
                    <h3 class="card-title">Player List</h3>
                    <p class="card-description">Manajemen data pemain akademi sepak bola.</p>
                </div>

                <div class="card-actions">
                    <a href="{{ route('players.create') }}" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        Tambah Player
                    </a>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead class="table-head">
                        <tr class="table-header-row">
                            <th class="table-header-cell">Info Player</th>
                            <th class="table-header-cell">Profil</th>
                            <th class="table-header-cell">Posisi</th>
                            <th class="table-header-cell">Status</th>
                            <th class="table-header-cell text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        @forelse ($players as $player)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <div class="flex items-center gap-3">
                                        <div class="table-avatar">
                                            @if ($player->photo && Storage::disk('public')->exists($player->photo))
                                                <img src="{{ asset('storage/' . $player->photo) }}"
                                                    alt="{{ $player->name }}" class="h-full w-full object-cover">
                                            @else
                                                <span class="avatar-placeholder">
                                                    {{ strtoupper(substr($player->name ?? 'P', 0, 2)) }}
                                                </span>
                                            @endif

                                        </div>
                                        <div>
                                            <a href="{{ route('players.show', $player->id_player) }}" class="table-title">
                                                {{ $player->name }}
                                            </a>
                                            <span class="table-subtitle">
                                                @if ($player->nick_name)
                                                    {{ $player->nick_name }} <br>
                                                @endif
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
                                    <span class="table-text">
                                        {{ $player->primary_position ?? '-' }}
                                    </span>
                                    <span class="table-subtitle">
                                        {{ $player->secondary_position ?? '-' }}
                                    </span>
                                </td>
                                <td class="table-cell">
                                    @if ($player->status)
                                        <span class="badge badge-success">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <div class="table-action">
                                        {{-- Detail --}}
                                        <a href="{{ route('players.show', $player->id_player) }}"
                                            class="btn-icon btn-icon-primary" title="Detail">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">

                                                <path
                                                    d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                                <path
                                                    d="M2.5 10C4.375 5.625 7.5 3.75 10 3.75C12.5 3.75 15.625 5.625 17.5 10C15.625 14.375 12.5 16.25 10 16.25C7.5 16.25 4.375 14.375 2.5 10Z"
                                                    stroke="currentColor" stroke-width="1.5" />
                                            </svg>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('players.edit', $player->id_player) }}"
                                            class="btn-icon btn-icon-warning" title="Edit">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">

                                                <path d="M13.75 2.5L17.5 6.25L6.25 17.5H2.5V13.75L13.75 2.5Z"
                                                    stroke="currentColor" stroke-width="1.5" />

                                            </svg>
                                        </a>

                                        {{-- Create Account --}}
                                        @if (!$player->id_user)
                                            <a href="{{ route('players.account.create', $player->id_player) }}"
                                                class="btn-icon btn-icon-success" title="Buat Akun">
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

                                        {{-- Delete --}}
                                        <button type="button"
                                            @click="deleteAction='{{ route('players.destroy', $player->id_player) }}';playerName='{{ addslashes($player->name) }}';showDeleteModal=true"
                                            class="btn-icon btn-icon-danger" title="Hapus">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path
                                                    d="M3.75 5H16.25M7.5 5V3.75C7.5 3.41848 7.6317 3.10054 7.86612 2.86612C8.10054 2.6317 8.41848 2.5 8.75 2.5H11.25C11.5815 2.5 11.8995 2.6317 12.1339 2.86612C12.3683 3.10054 12.5 3.41848 12.5 3.75V5M14.375 5V16.25C14.375 16.5815 14.2433 16.8995 14.0089 17.1339C13.7745 17.3683 13.4565 17.5 13.125 17.5H6.875C6.54348 17.5 6.22554 17.3685 5.99112 17.1339C5.7567 16.8995 5.625 16.5815 5.625 16.25V5H14.375Z"
                                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="table-empty">
                                    <div class="empty-state">
                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="text-gray-300 dark:text-gray-700 mb-3">

                                            <path
                                                d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z"
                                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        <h4 class="empty-title">
                                            Belum ada data Player
                                        </h4>
                                        <p class="empty-description">
                                            Tambah player sekarang
                                        </p>
                                        <a href="{{ route('players.create') }}" class="empty-link">
                                            Tambah sekarang
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($players->hasPages())
                <div class="table-footer">
                    {{ $players->links() }}
                </div>
            @endif

        </div>

        {{-- Delete Confirmation Modal --}}

        <div x-show="showDeleteModal" class="modal-overlay flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>


            <div class="modal-container modal-md" @click.away="showDeleteModal=false"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

                <div class="modal-header">
                    <div class="flex items-center gap-4">
                        <span class="modal-icon modal-icon-danger">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">

                                <path d="M12 9V14M12 17.01L12.01 16.9989M12 3L2 21H22L12 3Z" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="modal-title">
                                Konfirmasi Hapus
                            </h3>
                            <p class="modal-description">
                                Tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-body">
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Apakah Anda yakin ingin menghapus data Player
                        <strong class="font-semibold text-gray-800 dark:text-white" x-text="playerName">
                        </strong>?
                        Semua data terkait pemain ini akan dihapus secara permanen.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showDeleteModal=false">
                        Batal
                    </button>
                    <form :action="deleteAction" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            Hapus Permanen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
