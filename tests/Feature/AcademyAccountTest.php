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

    /**
     * Biodata Owner (issue13.md) -- WAJIB ikut dikirim tiap kali
     * 'create_account' => 1, karena Owner sekarang otomatis jadi Staff.
     */
    protected function ownerBiodataPayload(): array
    {
        return [
            'owner_full_name' => 'Budi Owner',
            'owner_gender' => 'male',
            'owner_birth_place' => 'Jakarta',
            'owner_birth_date' => '1985-01-01',
            'owner_phone' => '081234567890',
        ];
    }

    public function test_super_admin_bisa_buat_academy_sekaligus_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

        $response = $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $response->assertRedirect(route('academies.index'));

        $academy = Academy::where('code', $payload['code'])->first();

        $this->assertNotNull($academy->id_owner_user);
        $this->assertSame('owner@tes.com', $academy->owner->email);
        $this->assertTrue($academy->owner->hasRole('Owner'));
        $this->assertTrue($academy->owner->status);

        // Owner sekarang otomatis jadi Staff (issue13.md).
        $staff = \App\Models\Staff::where('id_user', $academy->owner->id_user)->first();

        $this->assertNotNull($staff);
        $this->assertSame('Budi Owner', $staff->full_name);
        $this->assertSame('active', $staff->activeContract->status);
        $this->assertSame('AD', $staff->activeContract->position->code);
        $this->assertSame('Permanent', $staff->activeContract->employmentType->name);
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

        $payload = $this->baseAcademyPayload(array_merge([
            'email' => 'kontak@academytes.com',
            'create_account' => 1,
            'owner_email' => 'owner@academytes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

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

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password-lama',
            'owner_password_confirmation' => 'password-lama',
        ], $this->ownerBiodataPayload()));

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();
        $oldHash = $academy->owner->password;

        $this->actingAs($superAdmin)->patch(route('academies.account.password', $academy));

        $this->assertNotSame($oldHash, $academy->owner->fresh()->password);
    }

    public function test_super_admin_bisa_nonaktifkan_akun_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

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

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

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

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academyDenganOwner = Academy::where('code', $payload['code'])->first();

        $response = $this->actingAs($superAdmin)->get(route('academies.index'));

        $response->assertOk();
        $response->assertSee(route('academies.account.create', $academyTanpaOwner), false);
        $response->assertDontSee(route('academies.account.create', $academyDenganOwner), false);
    }

    public function test_akun_owner_standalone_juga_membuat_staff(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $academy = Academy::factory()->create();

        // academies.account.store cuma membuat User+Staff -- role "Owner",
        // Employment Type "Permanent", dan Staff Position "Academy Director"
        // sendiri baru ada kalau academy dibuat lewat
        // AcademyManagementService::create(). Academy::factory() di sini
        // bypass alur itu, jadi disiapkan manual supaya
        // AccountService::assignRole() & StaffService::createForOwner()
        // (lewat findDefaultForOwner()) berhasil.
        Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        app(\App\Services\EmploymentTypeService::class)->createDefaultEmploymentTypes($academy);
        app(\App\Services\StaffPositionService::class)->createDefaultStaffPositions($academy);

        $this->actingAs($superAdmin)->post(route('academies.account.store', $academy), [
            'email' => 'owner@tes.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'full_name' => 'Citra Owner',
            'gender' => 'female',
            'birth_place' => 'Bandung',
            'birth_date' => '1990-05-05',
            'phone' => '089876543210',
        ]);

        $academy->refresh();
        $staff = \App\Models\Staff::where('id_user', $academy->id_owner_user)->first();

        $this->assertNotNull($staff);
        $this->assertSame('Citra Owner', $staff->full_name);
        $this->assertSame('active', $staff->activeContract->status);
        $this->assertSame('AD', $staff->activeContract->position->code);
        $this->assertSame('Permanent', $staff->activeContract->employmentType->name);
    }

    public function test_hapus_staff_pemilik_academy_ditolak(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $payload = $this->baseAcademyPayload(array_merge([
            'create_account' => 1,
            'owner_email' => 'owner@tes.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
        ], $this->ownerBiodataPayload()));

        $this->actingAs($superAdmin)->post(route('academies.store'), $payload);

        $academy = Academy::where('code', $payload['code'])->first();
        $staff = \App\Models\Staff::where('id_user', $academy->id_owner_user)->first();

        $this->expectException(\Exception::class);

        app(\App\Services\StaffService::class)->delete($staff);
    }
}
