<?php

namespace App\Models;

use App\Services\AcademyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Role sengaja TIDAK memakai trait BelongsToAcademy.
     * Trait itu selalu mengisi id_academy dari user yang login dan melempar
     * exception saat currentId() null, sehingga Super Admin tidak dapat
     * membuat Role System maupun role untuk academy lain.
     * id_academy diisi eksplisit oleh Service.
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'id_academy');
    }

    /**
     * Batasi role sesuai academy aktif. Super Admin melihat seluruh role.
     *
     * Ini scope LOKAL, dipanggil eksplisit oleh RoleService — bukan global
     * scope. Global scope pada Role akan ikut memfilter query
     * Permission::with('roles') milik PermissionRegistrar, yang hasilnya
     * di-cache dan dipakai bersama seluruh tenant. Lihat Bagian 4.3.
     */
    public function scopeForCurrentAcademy(Builder $query): Builder
    {
        $academyService = app(AcademyService::class);

        if ($academyService->isSuperAdmin()) {
            return $query;
        }

        return $query->where('roles.id_academy', $academyService->currentId());
    }

    /**
     * Override create() bawaan Spatie.
     *
     * Versi bawaan menolak role dengan nama sama TANPA memeriksa id_academy,
     * sehingga Academy B tidak dapat membuat role "Owner" ketika Academy A
     * sudah memilikinya. Lihat Bagian 4.1.
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= config('faos.guard');

        $academyId = $attributes['id_academy'] ?? null;

        $duplicate = static::query()
            ->where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->when(
                $academyId,
                fn (Builder $query) => $query->where('id_academy', $academyId),
                fn (Builder $query) => $query->whereNull('id_academy'),
            )
            ->exists();

        if ($duplicate) {
            throw new \Exception('Nama role sudah digunakan pada academy ini.');
        }

        return static::query()->create($attributes);
    }
}
