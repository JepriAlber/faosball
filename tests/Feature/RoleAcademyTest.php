<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AcademyManagementService;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAcademyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang
        // dibuat di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeRole(Academy $academy, string $name, array $permissions): Role
    {
        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        return $role;
    }

    protected function makeUser(Academy $academy, Role $role): User
    {
        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * INI ALASAN SELURUH REFACTOR INI ADA.
     */
    public function test_role_nama_sama_dapat_memiliki_permission_berbeda(): void
    {
        Permission::create(['name' => 'player.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'staff.view', 'guard_name' => 'web']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $ownerA = $this->makeUser($academyA, $this->makeRole($academyA, 'Owner', ['player.view']));
        $ownerB = $this->makeUser($academyB, $this->makeRole($academyB, 'Owner', ['player.view', 'staff.view']));

        $this->assertTrue($ownerA->can('player.view'));
        $this->assertFalse($ownerA->can('staff.view'));   // ← inti refactor

        $this->assertTrue($ownerB->can('player.view'));
        $this->assertTrue($ownerB->can('staff.view'));
    }

    /**
     * Mengunci override Role::create() di Tahap 2.
     */
    public function test_dua_academy_boleh_punya_role_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeRole($academyA, 'Owner', []);
        $this->makeRole($academyB, 'Owner', []);

        $this->assertSame(2, Role::where('name', 'Owner')->count());
    }

    /**
     * Skenario 3: Isolasi daftar — RoleService::paginate() milik Owner A
     * tidak boleh memuat role milik Academy B.
     */
    public function test_paginate_terisolasi_per_academy(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $roleA = $this->makeRole($academyA, 'Owner', []);
        $roleB = $this->makeRole($academyB, 'Owner', []);

        $ownerA = $this->makeUser($academyA, $roleA);

        $this->actingAs($ownerA);

        $roles = app(RoleService::class)->paginate();

        $this->assertTrue($roles->pluck('id')->contains($roleA->id));
        $this->assertFalse($roles->pluck('id')->contains($roleB->id));
    }

    /**
     * Skenario 4: Isolasi URL — Owner A tidak bisa membuka role milik
     * Academy B lewat URL walau lolos middleware permission.
     */
    public function test_akses_role_academy_lain_lewat_url_ditolak(): void
    {
        Permission::create(['name' => 'role.view', 'guard_name' => 'web']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        // Role A wajib punya role.view supaya lolos middleware permission,
        // dan yang benar-benar diuji adalah RolePolicy, bukan middleware-nya.
        $roleA = $this->makeRole($academyA, 'Owner', ['role.view']);
        $roleB = $this->makeRole($academyB, 'Owner', []);

        $ownerA = $this->makeUser($academyA, $roleA);

        $this->actingAs($ownerA)
            ->get(route('roles.show', $roleB))
            ->assertForbidden();
    }

    /**
     * Skenario 5: AccountService::assignRole() menolak role yang bukan
     * milik academy user.
     */
    public function test_assign_role_lintas_academy_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $roleA = $this->makeRole($academyA, 'Owner', []);
        $userB = $this->makeUser($academyB, $this->makeRole($academyB, 'Owner', []));

        $this->expectException(\Exception::class);

        app(AccountService::class)->assignRole($userB, $roleA);
    }

    /**
     * Skenario 6: Resolve role by name (string) mengambil baris milik
     * academy user yang benar, bukan baris pertama yang cocok namanya.
     */
    public function test_resolve_role_by_name_mengambil_role_academy_yang_benar(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeRole($academyA, 'Player', []);
        $playerRoleB = $this->makeRole($academyB, 'Player', []);

        $user = app(AccountService::class)->create([
            'id_academy' => $academyB->id_academy,
            'name' => 'Test Player',
            'email' => 'player@academyb.test',
            'password' => 'password',
        ], 'Player');

        $this->assertSame($playerRoleB->id, $user->roles->first()->id);
    }

    /**
     * Skenario 7: Academy baru otomatis dapat role default lengkap dengan
     * permission-nya, sesuai config('faos.role_templates'). Ini mengunci
     * risiko salah ketik nama permission di config yang hilang diam-diam
     * (lihat catatan Permission::whereIn() di RoleService::createDefaultRoles()).
     */
    public function test_academy_baru_mendapat_role_default_lengkap_dengan_permission(): void
    {
        $templates = config('faos.role_templates');

        $allPermissionNames = collect($templates)
            ->flatten()
            ->unique()
            ->values();

        foreach ($allPermissionNames as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        $academy = app(AcademyManagementService::class)->create([
            'name' => 'Academy Template Test',
            'code' => 'ATT',
            'address' => 'Jl. Test No. 1',
        ]);

        $this->assertSame(
            count($templates),
            Role::where('id_academy', $academy->id_academy)->count()
        );

        foreach ($templates as $roleName => $permissions) {

            $role = Role::where('id_academy', $academy->id_academy)
                ->where('name', $roleName)
                ->first();

            $this->assertNotNull($role, "Role \"{$roleName}\" tidak dibuat untuk academy baru.");

            $this->assertEqualsCanonicalizing(
                $permissions,
                $role->permissions->pluck('name')->toArray(),
                "Permission role \"{$roleName}\" tidak sesuai config('faos.role_templates')."
            );
        }
    }

    /**
     * Skenario 8: Super Admin melihat seluruh role lintas academy.
     */
    public function test_super_admin_melihat_seluruh_role(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $roleA = $this->makeRole($academyA, 'Owner', []);
        $roleB = $this->makeRole($academyB, 'Owner', []);

        $superAdmin = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $this->actingAs($superAdmin);

        $roles = app(RoleService::class)->paginate();

        $this->assertTrue($roles->pluck('id')->contains($roleA->id));
        $this->assertTrue($roles->pluck('id')->contains($roleB->id));
    }
}
