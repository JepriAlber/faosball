<?php

namespace App\Services;

use App\Support\PermissionPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Terapkan filter search/module ke query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['module'])) {
            $query->where('name', 'like', $filters['module'] . '.%');
        }
    }

    /**
     * Daftar permission untuk halaman index, dengan search/filter/sort.
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Permission::withCount('roles');

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
     * Daftar module yang sudah pernah dipakai, untuk opsi datalist "Module".
     */
    public function existingModules(): array
    {
        return Permission::query()
            ->pluck('name')
            ->map(fn (string $name) => PermissionPresenter::module($name))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Daftar action yang dikenali, untuk opsi datalist "Action".
     */
    public function actionOptions(): array
    {
        return PermissionPresenter::actions();
    }

    /**
     * Create Permission
     */
    public function create(array $data): Permission
    {
        return DB::transaction(function () use ($data) {

            return Permission::create([
                'name' => $data['name'],
                'guard_name' => config('faos.guard'),
            ]);

        });
    }

    public function detail(Permission $permission): array
    {
        $permission->loadCount('roles');
        $permission->load(['roles' => fn ($query) => $query->orderBy('name')]);

        return [
            'permission' => $permission,
            'presenter' => PermissionPresenter::present($permission),
            'roles' => $permission->roles,
        ];
    }

    /**
     * Delete Permission
     */
    public function delete(Permission $permission): bool
    {
        return DB::transaction(function () use ($permission) {

            if ($permission->roles()->exists()) {
                throw new \Exception(__('Permission masih digunakan oleh role, tidak dapat dihapus.'));
            }

            return $permission->delete();

        });
    }
}
