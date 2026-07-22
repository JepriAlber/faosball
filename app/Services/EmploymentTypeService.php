<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\EmploymentType;
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
     * Daftar employment type untuk halaman index. Tidak perlu filter
     * id_academy manual -- AcademyScope sudah menangani.
     */
    public function paginate(?int $perPage = null)
    {
        return EmploymentType::with('academy')
            ->latest()
            ->paginate($perPage ?? config('faos.pagination.default'));
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

    /**
     * Guard delete DITAMBAHKAN di issue11.md Tahap 9 (cek relasi ke staff)
     * setelah tabel `staff` ada. Untuk sekarang, delete polos.
     */
    public function delete(EmploymentType $employmentType): bool
    {
        return DB::transaction(fn () => $employmentType->delete());
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
}
