<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\TeamStaffPosition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeamStaffPositionService
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
        $query = TeamStaffPosition::with('academy')->withCount('teamStaff');

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
            $query = TeamStaffPosition::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return TeamStaffPosition::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {
                $query->where('status', true);
                if ($includeId) {
                    $query->orWhere('id_team_staff_position', $includeId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): TeamStaffPosition
    {
        return DB::transaction(fn () => TeamStaffPosition::create([
            'id_academy' => $this->resolveAcademyId($data),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(TeamStaffPosition $teamStaffPosition, array $data): TeamStaffPosition
    {
        return DB::transaction(function () use ($teamStaffPosition, $data) {

            $teamStaffPosition->update([
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $teamStaffPosition;
        });
    }

    public function delete(TeamStaffPosition $teamStaffPosition): bool
    {
        return DB::transaction(function () use ($teamStaffPosition) {

            if ($teamStaffPosition->teamStaff()->exists()) {
                throw new \Exception(__('Posisi ini masih digunakan oleh staff tim, tidak dapat dihapus. Nonaktifkan posisi ini kalau sudah tidak dipakai.'));
            }

            return $teamStaffPosition->delete();
        });
    }

    /**
     * Buat Team Staff Position default untuk academy baru dari
     * config('faos.team_staff_position_templates'). Pola sama
     * StaffPositionService::createDefaultStaffPositions() (issue16.md
     * Bagian 2b -- ini dimensi berbeda dari StaffPosition).
     */
    public function createDefaultTeamStaffPositions(Academy $academy): void
    {
        foreach (config('faos.team_staff_position_templates') as $name => $attributes) {

            TeamStaffPosition::create([
                'id_academy' => $academy->id_academy,
                'code' => $attributes['code'],
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }
}
