<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Support\PermissionPresenter;

class RoleService
{
    public function paginate(int $perPage = 10)
    {
        return Role::withCount([
            'permissions',
            'users',
        ])->latest()->paginate($perPage);
    }

    public function permissionGroups(): Collection
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn($permission) => explode('.', $permission->name)[0]);
    }
    

public function detail(Role $role): array
{
    $role->load([
        'permissions',
        'users',
    ]);

    $permissionGroups = $role->permissions
        ->sortBy('name')
        ->groupBy(fn($permission) => PermissionPresenter::module($permission->name))
        ->map(
            fn($permissions) => $permissions->map(
                fn($permission) => PermissionPresenter::present($permission)
            )
        );

    return [
        'role' => $role,
        'permissionGroups' => $permissionGroups,
    ];
}

    /**
     * Create Role
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {

            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => config('faos.guard'),
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return $role;

    });
}

    /**
     * Update Role
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {

              if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception('Role Super Admin tidak dapat diubah.');
            }

            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;

        });
    }

    /**
     * Delete Role
     */
    public function delete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {

           if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception('Role Super Admin tidak dapat dihapus.');
            }

            if ($role->users()->exists()) {
                throw new \Exception('Role masih digunakan oleh user, tidak dapat dihapus.');
            }

            return $role->delete();

        });
    }

    
}