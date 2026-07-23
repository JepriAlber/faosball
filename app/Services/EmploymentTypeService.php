<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\EmploymentType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EmploymentTypeService
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

    /**
     * Daftar employment type untuk halaman index.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = EmploymentType::with('academy')->withCount('contracts');

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
     * Jumlah employment type per status, untuk badge di tab halaman index.
     * $includeStatus=false -- hitungan tiap tab tidak boleh ikut kefilter
     * oleh status tab yang sedang aktif. Pola sama
     * AcademyManagementService::statusCounts().
     */
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = EmploymentType::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor(true),
            'inactive' => $countFor(false),
        ];
    }

    /**
     * Daftar employment type untuk dropdown di form Staff (issue11.md).
     *
     * $academyId null -> seluruh academy (Super Admin di form create Staff).
     * $includeId      -> type yang sedang dipakai staff tetap ikut walau
     *                    sudah dinonaktifkan, supaya nilainya tidak hilang
     *                    saat form edit disimpan ulang.
     */
    public function selectable(?string $academyId = null, ?string $includeId = null): Collection
    {
        return EmploymentType::query()
            ->when($academyId, fn ($query) => $query->where('id_academy', $academyId))
            ->where(function ($query) use ($includeId) {

                $query->where('status', true);

                if ($includeId) {
                    $query->orWhere('id_employment_type', $includeId);
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

    public function create(array $data): EmploymentType
    {
        return DB::transaction(function () use ($data) {

            return EmploymentType::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    public function update(EmploymentType $employmentType, array $data): EmploymentType
    {
        return DB::transaction(function () use ($employmentType, $data) {

            // id_academy sengaja TIDAK ikut diubah -- sama alasan PlayerType.
            $employmentType->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            return $employmentType;
        });
    }

    public function delete(EmploymentType $employmentType): bool
    {
        return DB::transaction(function () use ($employmentType) {

            if ($employmentType->contracts()->exists()) {
                throw new \Exception(__('Employment type masih digunakan oleh kontrak staff, tidak dapat dihapus. Nonaktifkan employment type ini kalau sudah tidak dipakai.'));
            }

            return $employmentType->delete();
        });
    }

    /**
     * Buat employment type default untuk academy baru dari
     * config('faos.employment_type_templates').
     */
    public function createDefaultEmploymentTypes(Academy $academy): void
    {
        foreach (config('faos.employment_type_templates') as $name => $attributes) {

            EmploymentType::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'description' => $attributes['description'] ?? null,
                'status' => true,
            ]);
        }
    }

    /**
     * Employment Type default untuk Staff yang mewakili Owner Academy --
     * "Permanent" sudah otomatis dibuat tiap Academy baru lewat
     * createDefaultEmploymentTypes(). WAJIB ada; kalau somehow terhapus,
     * lempar error jelas alih-alih diam-diam membuat Contract tanpa
     * id_employment_type (issue13.md).
     */
    public function findDefaultForOwner(Academy $academy): EmploymentType
    {
        $employmentType = EmploymentType::where('id_academy', $academy->id_academy)
            ->where('name', 'Permanent')
            ->first();

        if (! $employmentType) {
            throw new \Exception(__('Employment Type default "Permanent" untuk academy ini tidak ditemukan.'));
        }

        return $employmentType;
    }
}
