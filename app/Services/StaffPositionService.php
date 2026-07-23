<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StaffPositionService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Filter id_academy di sini aman dipakai siapa saja -- AcademyScope
     * sudah membatasi user academy biasa ke academy-nya sendiri, jadi
     * filter ini praktis cuma berguna (dan cuma ditampilkan) untuk Super
     * Admin. Pola sama RoleService::applyFilters().
     */
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
        $query = StaffPosition::with(['academy', 'role'])->withCount('contracts');

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    /**
     * Jumlah staff position per status, untuk badge di tab halaman index.
     * $includeStatus=false -- hitungan tiap tab tidak boleh ikut kefilter
     * oleh status tab yang sedang aktif. Pola sama
     * AcademyManagementService::statusCounts().
     */
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = StaffPosition::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor(true),
            'inactive' => $countFor(false),
        ];
    }

    /**
     * Daftar staff position untuk dropdown di form Staff (issue11.md).
     * Pola sama persis PlayerTypeService::selectable().
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return StaffPosition::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_staff_position', $includeId);
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

    public function create(array $data): StaffPosition
    {
        return DB::transaction(function () use ($data) {

            return StaffPosition::create([
                'id_academy' => $this->resolveAcademyId($data),
                'role_id' => $data['role_id'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'is_coach' => $data['is_coach'] ?? false,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(StaffPosition $staffPosition, array $data): StaffPosition
    {
        return DB::transaction(function () use ($staffPosition, $data) {

            // id_academy sengaja TIDAK ikut diubah -- sama alasan PlayerType.
            $staffPosition->update([
                'role_id' => $data['role_id'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'is_coach' => $data['is_coach'] ?? false,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $staffPosition;
        });
    }

    public function delete(StaffPosition $staffPosition): bool
    {
        return DB::transaction(function () use ($staffPosition) {

            if ($staffPosition->contracts()->exists()) {
                throw new \Exception(__('Staff position masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan staff position ini kalau sudah tidak dipakai.'));
            }

            return $staffPosition->delete();
        });
    }

    /**
     * Buat staff position default untuk academy baru dari
     * config('faos.staff_position_templates'). WAJIB dipanggil SETELAH
     * RoleService::createDefaultRoles() -- role_id di-resolve dengan
     * mencari role yang namanya cocok ('default_role') pada academy yang
     * SAMA. Kalau role belum ada (urutan pemanggilan salah), role_id
     * jatuh ke null -- tidak error, tapi kehilangan nilai default-nya.
     */
    public function createDefaultStaffPositions(Academy $academy): void
    {
        foreach (config('faos.staff_position_templates') as $name => $attributes) {

            $roleId = null;

            if (! empty($attributes['default_role'])) {
                $roleId = Role::where('id_academy', $academy->id_academy)
                    ->where('name', $attributes['default_role'])
                    ->value('id');
            }

            StaffPosition::create([
                'id_academy' => $academy->id_academy,
                'role_id' => $roleId,
                'code' => $attributes['code'],
                'name' => $name,
                'is_coach' => $attributes['is_coach'] ?? false,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }

    /**
     * Staff Position default untuk Owner Academy -- "Academy Director"
     * (code "AD") sudah otomatis dibuat tiap Academy baru lewat
     * createDefaultStaffPositions(). Cari pakai `code`, bukan `name` --
     * code lebih stabil kalau label pernah diterjemahkan/diubah nanti
     * (issue13.md).
     */
    public function findDefaultForOwner(Academy $academy): StaffPosition
    {
        $staffPosition = StaffPosition::where('id_academy', $academy->id_academy)
            ->where('code', 'AD')
            ->first();

        if (! $staffPosition) {
            throw new \Exception(__('Staff Position default "Academy Director" untuk academy ini tidak ditemukan.'));
        }

        return $staffPosition;
    }
}
