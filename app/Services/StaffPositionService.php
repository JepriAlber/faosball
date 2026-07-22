<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StaffPositionService
{
    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    public function paginate(?int $perPage = null)
    {
        return StaffPosition::with(['academy', 'role'])
            ->withCount('contracts')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
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
