<?php

namespace App\Services;

use App\Models\Academy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademyManagementService
{

    protected RoleService $roleService;
    protected PlayerTypeService $playerTypeService;
    protected PlayerCategoryService $playerCategoryService;

    public function __construct(
        RoleService $roleService,
        PlayerTypeService $playerTypeService,
        PlayerCategoryService $playerCategoryService
    ) {
        $this->roleService = $roleService;
        $this->playerTypeService = $playerTypeService;
        $this->playerCategoryService = $playerCategoryService;
    }

    /**
     * Upload academy logo
     */
    protected function uploadLogo($file, string $academyCode): string
    {
        $filename = strtoupper($academyCode)
            . '-'
            . Str::uuid()
            . '.'
            . $file->getClientOriginalExtension();


        return $file->storeAs(
            'academies/logo',
            $filename,
            'public'
        );
    }


    /**
     * Delete academy logo
     */
    protected function deleteLogo(?string $logo): void
    {
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }
    }


    /**
     * Generate academy slug
     */
    protected function generateSlug(string $name): string
    {
        return Str::slug($name);
    }


    /*
    |--------------------------------------------------------------------------
    | List / Filter Academy
    |--------------------------------------------------------------------------
    */

    /**
     * Terapkan filter search/status ke query.
     *
     * $includeStatus = false dipakai oleh statusCounts() -- hitungan tiap tab
     * status tidak boleh ikut kefilter oleh status tab yang sedang aktif,
     * supaya angkanya tetap utuh saat user pindah tab. Sama seperti pola di
     * PlayerService::applyFilters().
     */
    protected function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if (!empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($includeStatus && isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status'] === 'active');
        }
    }

    /**
     * Daftar academy untuk halaman index, dengan search/filter/sort.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Academy::query();

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
     * Jumlah academy per status, untuk badge di tabs halaman index.
     *
     * Cuma dua state (boolean), jadi cukup dua query where()->count() --
     * tidak perlu groupBy seperti PlayerService::statusCounts() yang punya
     * 4 nilai enum.
     */
    public function statusCounts(array $filters = []): array
    {
        $countFor = function (bool $status) use ($filters) {

            $query = Academy::query();

            $this->applyFilters($query, $filters, includeStatus: false);

            return $query->where('status', $status)->count();
        };

        return [
            'active' => $countFor(true),
            'inactive' => $countFor(false),
        ];
    }


    /**
     * Create academy
     */
    public function create(array $data): Academy
    {
        return DB::transaction(function () use ($data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;

            if (isset($data['logo'])) {
                $data['logo'] = $this->uploadLogo(
                    $data['logo'],
                    $data['code']
                );
            }

            $academy = Academy::create($data);

            $this->roleService->createDefaultRoles($academy);
            $this->playerTypeService->createDefaultPlayerTypes($academy);
            $this->playerCategoryService->createDefaultPlayerCategories($academy);

            return $academy;
        });
    }


    /**
     * Update academy
     */
    public function update(Academy $academy, array $data): Academy
    {
        return DB::transaction(function () use ($academy, $data) {

            $data['code'] = strtoupper($data['code']);
            $data['slug'] = $this->generateSlug($data['name']);
            $data['status'] = $data['status'] ?? false;


            if (isset($data['logo'])) {

                $this->deleteLogo($academy->logo);

                $data['logo'] = $this->uploadLogo(
                    $data['logo'],
                    $data['code']
                );
            }


            $academy->update($data);

            return $academy;
        });
    }


    /**
     * Delete academy
     */
    public function delete(Academy $academy): bool
    {
        return DB::transaction(function () use ($academy) {

            $this->deleteLogo($academy->logo);

            return $academy->delete();
        });
    }


}