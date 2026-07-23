@extends('layouts.app', ['page' => 'employment-contracts'])

@section('title', $title . ' - ' . config('app.name'))

@section('content')

    <x-breadcrumb :title="$title" :items="$breadcrumb" />
    <x-alert />

    <div class="card">

        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('Daftar Kontrak Kerja') }}</h3>
                <p class="card-description">{{ __('Seluruh kontrak kerja staff -- cari kontrak yang aktif atau akan berakhir pada bulan tertentu. Untuk membuat/mengubah kontrak, buka halaman detail staff yang bersangkutan.') }}</p>
            </div>
        </div>

        <div class="border-b border-gray-100 p-4 dark:border-gray-800">
            @php
                $totalContracts = array_sum($statusCounts);
            @endphp
            <x-table.tabs route="employment-contracts.index" :active="$filters['status'] ?? ''" :tabs="[
                '' => ['label' => __('Semua'), 'count' => $totalContracts],
                'draft' => ['label' => __('Draft'), 'count' => $statusCounts['draft']],
                'active' => ['label' => __('Active'), 'count' => $statusCounts['active']],
                'completed' => ['label' => __('Completed'), 'count' => $statusCounts['completed']],
                'terminated' => ['label' => __('Terminated'), 'count' => $statusCounts['terminated']],
                'cancelled' => ['label' => __('Cancelled'), 'count' => $statusCounts['cancelled']],
            ]" />
        </div>

        <x-table.toolbar route="employment-contracts.index" :filters="$filters" placeholder="{{ __('Cari nama staff atau kode kontrak...') }}">

            <div class="form-group">
                <label class="form-label">{{ __('Bulan Berakhir Kontrak') }}</label>
                <input type="month" name="end_month" value="{{ $filters['end_month'] ?? '' }}" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Urutkan') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('Terbaru') }}</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('Terlama') }}</option>
                    <option value="end_date_asc" @selected(($filters['sort'] ?? '') === 'end_date_asc')>{{ __('Tanggal Berakhir Terdekat') }}</option>
                    <option value="end_date_desc" @selected(($filters['sort'] ?? '') === 'end_date_desc')>{{ __('Tanggal Berakhir Terjauh') }}</option>
                </select>
            </div>

            @if ($isSuperAdmin)
                <div class="form-group">
                    <label class="form-label">{{ __('Academy') }}</label>
                    <select name="id_academy" class="form-select">
                        <option value="">{{ __('Semua Academy') }}</option>
                        @foreach ($academies as $academy)
                            <option value="{{ $academy->id_academy }}" @selected(($filters['id_academy'] ?? '') === $academy->id_academy)>
                                {{ $academy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </x-table.toolbar>

        @php
            $contractStatusBadge = fn ($status) => match ($status) {
                'draft' => ['label' => __('Draft'), 'class' => 'badge-secondary'],
                'active' => ['label' => __('Active'), 'class' => 'badge-success'],
                'completed' => ['label' => __('Completed'), 'class' => 'badge-primary'],
                'terminated' => ['label' => __('Terminated'), 'class' => 'badge-danger'],
                'cancelled' => ['label' => __('Cancelled'), 'class' => 'badge-secondary'],
            };
        @endphp

        <div class="table-wrapper">
            <table class="table">

                <thead class="table-head">
                    <tr class="table-header-row">
                        <th class="table-header-cell">{{ __('Staff') }}</th>
                        <th class="table-header-cell">{{ __('Posisi & Jenis') }}</th>
                        <th class="table-header-cell">{{ __('Mulai') }}</th>
                        <th class="table-header-cell">{{ __('Berakhir') }}</th>
                        @if ($isSuperAdmin)
                            <th class="table-header-cell">{{ __('Academy') }}</th>
                        @endif
                        <th class="table-header-cell">{{ __('Status') }}</th>
                        <th class="table-header-cell text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>

                <tbody class="table-body">

                    @forelse ($contracts as $contract)

                        <tr class="table-row">

                            <td class="table-cell">
                                <div>
                                    <span class="table-title">{{ $contract->staff->full_name }}</span>
                                    <span class="table-subtitle">{{ $contract->contract_code }}</span>
                                </div>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->position->name ?? '-' }}</span>
                                <span class="table-subtitle">{{ $contract->employmentType->name ?? '-' }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->start_date?->format('d M Y') }}</span>
                            </td>

                            <td class="table-cell">
                                <span class="table-text">{{ $contract->end_date?->format('d M Y') ?? __('Tanpa batas') }}</span>
                            </td>

                            @if ($isSuperAdmin)
                                <td class="table-cell">
                                    <span class="badge badge-secondary">{{ $contract->academy->name }}</span>
                                </td>
                            @endif

                            <td class="table-cell">
                                <span class="badge {{ $contractStatusBadge($contract->status)['class'] }}">
                                    {{ $contractStatusBadge($contract->status)['label'] }}
                                </span>
                            </td>

                            <td class="table-cell text-right">
                                <div class="table-action">
                                    <a href="{{ route('staff.show', $contract->staff) }}" class="btn-icon" title="{{ __('Lihat Staff') }}">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                            <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                                        </svg>
                                    </a>
                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="table-empty">
                                <div class="empty-state">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="text-gray-300 dark:text-gray-700 mb-3">
                                        <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                                    </svg>
                                    <h4 class="empty-title">{{ __('Belum ada kontrak yang cocok') }}</h4>
                                    <p class="empty-description">{{ __('Coba ubah kata kunci pencarian atau filter yang dipakai.') }}</p>
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        <!-- Card List (mobile & tablet) -->
        <div class="table-card-list">
            @forelse ($contracts as $contract)
                <div class="table-card">
                    <div class="table-card-header">
                        <div class="min-w-0">
                            <span class="table-title truncate">{{ $contract->staff->full_name }}</span>
                            <span class="table-subtitle">{{ $contract->contract_code }}</span>
                        </div>

                        <span class="badge {{ $contractStatusBadge($contract->status)['class'] }} shrink-0">
                            {{ $contractStatusBadge($contract->status)['label'] }}
                        </span>
                    </div>

                    <div class="table-card-body">
                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Posisi & Jenis') }}</span>
                            <span class="table-text">{{ $contract->position->name ?? '-' }} &middot; {{ $contract->employmentType->name ?? '-' }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Mulai') }}</span>
                            <span class="table-text">{{ $contract->start_date?->format('d M Y') }}</span>
                        </div>

                        <div class="table-card-field">
                            <span class="table-card-label">{{ __('Berakhir') }}</span>
                            <span class="table-text">{{ $contract->end_date?->format('d M Y') ?? __('Tanpa batas') }}</span>
                        </div>

                        @if ($isSuperAdmin)
                            <div class="table-card-field">
                                <span class="table-card-label">{{ __('Academy') }}</span>
                                <span class="badge badge-secondary w-fit">{{ $contract->academy->name }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="table-card-actions">
                        <a href="{{ route('staff.show', $contract->staff) }}" class="btn-icon" title="{{ __('Lihat Staff') }}">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M1.66666 10C1.66666 10 4.16666 4.16667 10 4.16667C15.8333 4.16667 18.3333 10 18.3333 10C18.3333 10 15.8333 15.8333 10 15.8333C4.16666 15.8333 1.66666 10 1.66666 10Z" stroke="currentColor" stroke-width="1.5" />
                                <path d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="table-card">
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" class="text-gray-300 dark:text-gray-700 mb-3">
                            <path d="M24 14V18M24 30H24.02M42 24C42 33.9411 33.9411 42 24 42C14.01 42 6 33.9411 6 24C6 14.0589 14.01 6 24 6C33.9411 6 42 14.0589 42 24Z" stroke="currentColor" stroke-width="2.5" />
                        </svg>
                        <h4 class="empty-title">{{ __('Belum ada kontrak yang cocok') }}</h4>
                        <p class="empty-description">{{ __('Coba ubah kata kunci pencarian atau filter yang dipakai.') }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($contracts->hasPages())
            <div class="table-footer">
                {{ $contracts->links() }}
            </div>
        @endif

    </div>

@endsection
