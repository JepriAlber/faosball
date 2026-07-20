<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AcademyAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeSuperAdmin(): User
    {
        foreach (['academy.view', 'academy.create', 'academy.update', 'academy.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

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

    protected function baseAcademyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Academy Tes',
            'code' => 'TES' . fake()->unique()->numberBetween(100, 999),
            'phone' => '081234567890',
            'email' => 'academy@tes.com',
            'address' => 'Jl. Tes',
            'tagline' => 'Tagline Tes',
            'status' => true,
            'subscription_type' => 'monthly',
            'subscription_fee' => 100000,
            'subscription_started_at' => now()->toDateString(),
            'subscription_ends_at' => now()->addMonth()->toDateString(),
            'primary_color' => '#465fff',
        ], $overrides);
    }

    public function test_super_admin_bisa_buat_academy_sekaligus_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $response->assertRedirect(route('academies.index'));

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertNotNull($academy->id_owner_user);
        $this->assertSame('owner@tes.com', $academy->owner->email);
        $this->assertTrue($academy->owner->hasRole('Owner'));
        $this->assertTrue($academy->owner->status);
    }

    public function test_academy_tanpa_toggle_create_account_tidak_membuat_user(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload(['create_account' => 0]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertNull($academy->id_owner_user);
    }

    public function test_toggle_aktif_tapi_owner_email_kosong_ditolak(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $response->assertSessionHasErrors('owner_email');
        $this->assertDatabaseMissing('academies', ['code' => $payload['code']]);
    }

    public function test_owner_email_tidak_bentrok_dengan_email_kontak_academy(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'email' => 'kontak@academytes.com',
            'create_account' => 1,
            'owner_email' => 'owner@academytes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertSame('kontak@academytes.com', $academy->email);
        $this->assertSame('owner@academytes.com', $academy->owner->email);
    }

    public function test_role_academy_biasa_ditolak_403_akses_route_account(): void
    {
        $academy = Academy::factory()->create();

        Permission::firstOrCreate(['name' => 'player.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Staff', 'guard_name' => 'web']);
        $role->syncPermissions(['player.view']);

        $staff = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $staff->assignRole($role);

        $response = $this->actingAs($staff)->get(route('academies.account.create', $academy));

        $response->assertForbidden();
    }

    public function test_super_admin_bisa_reset_password_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password-lama',
            'owner_password_confirmation' => 'password-lama',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();
        $oldHash = $academy->owner->password;

        $this->actingAs($superAdmin)->patch(route('academies.account.password', $academy));

        $this->assertNotSame($oldHash, $academy->owner->fresh()->password);
    }

    public function test_super_admin_bisa_nonaktifkan_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertTrue($academy->owner->status);

        $this->actingAs($superAdmin)->patch(route('academies.account.status', $academy));

        $this->assertFalse($academy->owner->fresh()->status);
    }

    public function test_halaman_detail_academy_tampil_tombol_buat_akun_saat_belum_ada_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $academy = Academy::factory()->create();

        $response = $this->actingAs($superAdmin)->get(route('academies.show', $academy));

        $response->assertOk();
        $response->assertSee(route('academies.account.create', $academy), false);
    }

    public function test_halaman_account_create_dan_edit_tampil_dengan_benar(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();

        $this->actingAs($superAdmin)
            ->get(route('academies.account.edit', $academy))
            ->assertOk()
            ->assertSee('owner@tes.com');

        $academyTanpaOwner = Academy::factory()->create();

        $this->actingAs($superAdmin)
            ->get(route('academies.account.create', $academyTanpaOwner))
            ->assertOk();
    }

    public function test_halaman_index_tampilkan_tombol_buat_akun_hanya_untuk_academy_tanpa_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $academyTanpaOwner = Academy::factory()->create();

        $payload = $this->baseAcademyPayload([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ]);

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academyDenganOwner = Academy::where('code', $payload['code'])->first();

        $response = $this->actingAs($superAdmin)->get(route('academies.index'));

        $response->assertOk();
        $response->assertSee(route('academies.account.create', $academyTanpaOwner), false);
        $response->assertDontSee(route('academies.account.create', $academyDenganOwner), false);
    }
}
