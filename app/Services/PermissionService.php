<?php

namespace App\Services;

use App\Support\PermissionPresenter;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function paginate(int $perPage = 10)
    {
        return Permission::withCount('roles')->latest()->paginate($perPage);
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
                throw new \Exception('Permission masih digunakan oleh role, tidak dapat dihapus.');
            }

            return $permission->delete();

        });
    }
}
