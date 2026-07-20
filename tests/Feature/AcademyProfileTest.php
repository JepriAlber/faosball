<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeAcademyUser(Academy $academy, array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::factory()->create([
            'id_academy' => $academy->id_academy,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
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

    public function test_owner_bisa_update_profil_academy_sendiri(): void
    {
        $academy = Academy::factory()->create(['name' => 'Nama Lama']);
        $owner = $this->makeAcademyUser($academy, ['academy_profile.update']);

        $response = $this->actingAs($owner)->patch(route('academy.profile.update'), [
            'name' => 'Nama Baru',
            'tagline' => 'Tagline Baru',
            'phone' => '081234567890',
            'email' => 'baru@academy.com',
            'address' => 'Alamat Baru',
            'primary_color' => '#465fff',
        ]);

        $response->assertRedirect(route('academy.profile.edit'));
        $this->assertSame('Nama Baru', $academy->fresh()->name);
        $this->assertSame('#465fff', $academy->fresh()->primary_color);
    }

    public function test_owner_tidak_bisa_selipkan_perubahan_code_status_atau_subscription(): void
    {
        $academy = Academy::factory()->create([
            'code' => 'LAMA',
            'status' => true,
            'subscription_fee' => 500000,
        ]);
        $owner = $this->makeAcademyUser($academy, ['academy_profile.update']);

        $this->actingAs($owner)->patch(route('academy.profile.update'), [
            'name' => $academy->name,
            'tagline' => $academy->tagline,
            'phone' => $academy->phone,
            'email' => $academy->email,
            'address' => $academy->address,
            'primary_color' => '#465fff',
            // Field terlarang, dikirim seolah request yang dikarang lewat DevTools:
            'code' => 'BARU',
            'status' => false,
            'subscription_fee' => 1,
        ]);

        $fresh = $academy->fresh();
        $this->assertSame('LAMA', $fresh->code);
        $this->assertTrue($fresh->status);
        $this->assertEquals(500000, $fresh->subscription_fee);
    }

    public function test_role_tanpa_permission_ditolak_403(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeAcademyUser($academy, []);

        $response = $this->actingAs($staff)->get(route('academy.profile.edit'));

        $response->assertForbidden();
    }

    public function test_super_admin_tanpa_current_academy_dapat_404_bukan_crash(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('academy.profile.edit'));

        $response->assertNotFound();
    }

    public function test_menu_profil_academy_tidak_tampil_untuk_super_admin(): void
    {
        // Gate::before() meloloskan Super Admin dari @can('academy_profile.update'),
        // jadi sidebar butuh guard tambahan supaya menu ini tidak ikut tampil --
        // modul ini tidak relevan untuk Super Admin (sudah ada Academy Management).
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('academy.profile.edit'), false);
    }

    public function test_menu_profil_academy_tampil_untuk_owner(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeAcademyUser($academy, ['academy_profile.update']);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('academy.profile.edit'), false);
    }
}
