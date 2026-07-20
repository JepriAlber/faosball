<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeAcademyOwner(Academy $academy): User
    {
        // Owner sengaja TIDAK diberi academy.*, mensimulasikan role_templates
        // default -- academy.* memang tidak pernah ada di template manapun.
        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'academy.view', 'guard_name' => 'web']);

        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_status_aktif_saat_masih_jauh_dari_jatuh_tempo(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->addMonths(2),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('aktif', $status);
    }

    public function test_status_akan_berakhir_dalam_tujuh_hari(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->addDays(3),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('akan_berakhir', $status);
    }

    public function test_status_kadaluarsa_setelah_lewat_tanggal(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => now()->subDays(1),
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('kadaluarsa', $status);
    }

    public function test_status_belum_diatur_saat_ends_at_kosong(): void
    {
        $academy = Academy::factory()->create([
            'subscription_ends_at' => null,
        ]);

        $status = app(AcademyManagementService::class)->subscriptionStatus($academy);

        $this->assertSame('belum_diatur', $status);
    }

    public function test_owner_academy_ditolak_403_akses_academies_index(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeAcademyOwner($academy);

        $response = $this->actingAs($owner)->get(route('academies.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_bisa_akses_academies_index(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('academies.index'));

        $response->assertOk();
    }

    public function test_halaman_create_academy_render_tanpa_error(): void
    {
        Permission::firstOrCreate(['name' => 'academy.create', 'guard_name' => 'web']);
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('academies.create'));

        $response->assertOk();
        $response->assertSee('Logo Academy');
    }

    public function test_halaman_edit_academy_render_tanpa_error(): void
    {
        Permission::firstOrCreate(['name' => 'academy.update', 'guard_name' => 'web']);
        $superAdmin = $this->makeSuperAdmin();
        $academy = Academy::factory()->create();

        $response = $this->actingAs($superAdmin)->get(route('academies.edit', $academy));

        $response->assertOk();
        $response->assertSee('Logo Academy');
    }
}
