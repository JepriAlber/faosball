<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Role;
use App\Support\PermissionPresenter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleService
{
    /**
     * Value sentinel untuk filter dropdown "Role System" (id_academy NULL) --
     * dipilih supaya tidak pernah bentrok dengan UUID id_academy asli.
     */
    public const SYSTEM_ROLE_FILTER = '__system__';

    protected AcademyService $academyService;

    public function __construct(AcademyService $academyService)
    {
        $this->academyService = $academyService;
    }

    /**
     * Terapkan filter search/academy ke query.
     *
     * Filter id_academy cuma berguna buat Super Admin -- user academy biasa
     * sudah dibatasi ke 1 academy lewat scopeForCurrentAcademy(), jadi
     * filter ini tidak pernah mengubah hasil untuk mereka (aman diabaikan).
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['id_academy'])) {
            if ($filters['id_academy'] === self::SYSTEM_ROLE_FILTER) {
                $query->whereNull('roles.id_academy');
            } else {
                $query->where('roles.id_academy', $filters['id_academy']);
            }
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Role::forCurrentAcademy()
            ->with('academy')
            ->withCount(['permissions', 'users']);

        $this->applyFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        return $query->paginate(config('faos.pagination.default'));
    }

    public function permissionGroups(): Collection
    {
        return Permission::orderBy('name')
            ->get()
            ->groupBy(fn ($permission) => explode('.', $permission->name)[0]);
    }

    public function detail(Role $role): array
    {
        $role->load(['permissions', 'users']);

        $permissionGroups = $role->permissions
            ->sortBy('name')
            ->groupBy(fn ($permission) => PermissionPresenter::module($permission->name))
            ->map(
                fn ($permissions) => $permissions->map(
                    fn ($permission) => PermissionPresenter::present($permission)
                )
            );

        return [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
        ];
    }

    /**
     * Tentukan id_academy untuk role baru.
     *
     * User academy : otomatis dari academy miliknya, input form DIABAIKAN.
     * Super Admin  : dari pilihan academy di form (null = Role System).
     */
    protected function resolveAcademyId(array $data): ?string
    {
        if (! $this->academyService->isSuperAdmin()) {
            return $this->academyService->currentId();
        }

        return $data['id_academy'] ?? null;
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {

            $role = Role::create([
                'id_academy' => $this->resolveAcademyId($data),
                'name' => $data['name'],
                'guard_name' => config('faos.guard'),
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {

            if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception(__('Role Super Admin tidak dapat diubah.'));
            }

            // id_academy sengaja TIDAK ikut diubah.
            // Role tidak dapat berpindah academy.
            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    public function delete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {

            if ($role->name === config('faos.super_admin_role')) {
                throw new \Exception(__('Role Super Admin tidak dapat dihapus.'));
            }

            if ($role->users()->exists()) {
                throw new \Exception(__('Role masih digunakan oleh user, tidak dapat dihapus.'));
            }

            return $role->delete();
        });
    }

    /**
     * Buat role default untuk academy baru dari config('faos.role_templates').
     *
     * Academy di-pass eksplisit (bukan dari academy aktif) karena yang membuat
     * academy adalah Super Admin, yang id_academy-nya null.
     */
    public function createDefaultRoles(Academy $academy): void
    {
        foreach (config('faos.role_templates') as $name => $permissions) {

            $role = Role::create([
                'id_academy' => $academy->id_academy,
                'name' => $name,
                'guard_name' => config('faos.guard'),
            ]);

            $role->syncPermissions(
                Permission::whereIn('name', $permissions)->get()
            );
        }
    }
}
