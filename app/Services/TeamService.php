<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeamService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }

        if (! empty($filters['id_academy'])) {
            $query->where('id_academy', $filters['id_academy']);
        }

        if (! empty($filters['id_season'])) {
            $query->where('id_season', $filters['id_season']);
        }

        if (! empty($filters['id_player_category'])) {
            $query->where('id_player_category', $filters['id_player_category']);
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Team::with(['academy', 'season', 'playerCategory'])
            ->withCount(['activeTeamPlayers', 'activeTeamStaff']);

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
            $query = Team::query();
            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return ['active' => $countFor(true), 'inactive' => $countFor(false)];
    }

    /**
     * Pola sama StaffService::generateStaffCode() -- prefix "TM", 3 digit
     * berurutan lintas academy (Team bukan per-academy sequence supaya
     * simpel, beda dari staff_code yang per-academy).
     */
    protected function generateTeamCode(): string
    {
        return DB::transaction(function () {

            $last = Team::withoutGlobalScopes()
                ->where('code', 'like', 'TM%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->first();

            $next = $last ? ((int) substr($last->code, 2)) + 1 : 1;

            do {
                $code = 'TM' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                $exists = Team::withoutGlobalScopes()->where('code', $code)->exists();
                $next++;
            } while ($exists);

            return $code;
        });
    }

    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Team
    {
        return DB::transaction(fn () => Team::create([
            'id_academy' => $this->resolveAcademyId($data),
            'id_season' => $data['id_season'],
            'id_player_category' => $data['id_player_category'],
            'code' => $this->generateTeamCode(),
            'name' => $data['name'],
            'team_type' => $data['team_type'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? true,
        ]));
    }

    public function update(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {

            // id_academy & code sengaja TIDAK ikut diubah.
            $team->update([
                'id_season' => $data['id_season'],
                'id_player_category' => $data['id_player_category'],
                'name' => $data['name'],
                'team_type' => $data['team_type'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $team;
        });
    }

    /**
     * SoftDeletes (archive), BUKAN hard delete -- lihat issue16.md
     * Bagian 2a. Ditolak kalau masih ada Team Player/Team Staff aktif.
     */
    public function delete(Team $team): bool
    {
        return DB::transaction(function () use ($team) {

            if ($team->activeTeamPlayers()->exists() || $team->activeTeamStaff()->exists()) {
                throw new \Exception(__('Tim ini masih memiliki player/staff yang aktif, keluarkan semua anggota aktif terlebih dahulu sebelum menghapus tim.'));
            }

            return $team->delete();
        });
    }
}
