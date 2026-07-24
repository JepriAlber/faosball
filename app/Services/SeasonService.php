<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Season;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Season::with('academy')->withCount('teams');

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = Season::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    /**
     * Dropdown Season di form Team. $includeId -- season yang sedang
     * dipakai Team tetap ikut walau sudah dinonaktifkan.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return Season::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {
                $query->where('status', true);
                if ($includeId) {
                    $query->orWhere('id_season', $includeId);
                }
            })
            ->orderByDesc('name')
            ->get();
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Season
    {
        return DB::transaction(fn () => Season::create([
            'id_academy' => $this->resolveAcademyId($data),
            'name' => $data['name'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(Season $season, array $data): Season
    {
        return DB::transaction(function () use ($season, $data) {

            $season->update([
                'name' => $data['name'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $season;
        });
    }

    public function delete(Season $season): bool
    {
        return DB::transaction(function () use ($season) {

            if ($season->teams()->exists()) {
                throw new \Exception(__('Season masih digunakan oleh tim, tidak dapat dihapus. Nonaktifkan season ini kalau sudah tidak dipakai.'));
            }

            return $season->delete();
        });
    }
}
