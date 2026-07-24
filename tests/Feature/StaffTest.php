<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeStaffPrereqs(Academy $academy): array
    {
        return [
            'employmentType' => EmploymentType::factory()->create(['id_academy' => $academy->id_academy]),
            'staffPosition' => StaffPosition::factory()->create(['id_academy' => $academy->id_academy]),
        ];
    }

    protected function actingAsOwner(Academy $academy): User
    {
        Permission::firstOrCreate(['name' => 'staff.create', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('name', 'staff.create')->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);
        $this->actingAs($owner);

        return $owner;
    }

    protected function actingAsSuperAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'staff.create', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => null, 'name' => 'Super Admin Test', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::where('name', 'staff.create')->get());

        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);
        $superAdmin->assignRole($role);
        $this->actingAs($superAdmin);

        return $superAdmin;
    }

    public function test_create_staff_generate_staff_code_otomatis(): void
    {
        $academy = Academy::factory()->create(['code' => 'FCX']);
        $prereqs = $this->makeStaffPrereqs($academy);

        $staff = app(StaffService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'full_name' => 'Budi Santoso',
            'gender' => 'male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'phone' => '081234567890',
        ]);

        $this->assertStringStartsWith('FCX' . now()->format('y'), $staff->staff_code);
        $this->assertSame('Indonesia', $staff->nationality);

        // Kepegawaian sekarang milik EmploymentContract, bukan kolom staff
        // langsung (issue12.md Bagian 2a) -- staff baru harus otomatis
        // punya 1 Contract berstatus Active.
        $this->assertNotNull($staff->activeContract);
        $this->assertSame('active', $staff->activeContract->status);
    }

    public function test_hapus_staff_ikut_menghapus_akun_terkait(): void
    {
        $academy = Academy::factory()->create();
        $user = User::factory()->create(['id_academy' => $academy->id_academy]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_user' => $user->id_user,
        ]);

        app(StaffService::class)->delete($staff);

        // User model pakai SoftDeletes -- baris tetap ada di tabel dengan
        // deleted_at terisi, bukan benar-benar hilang dari database.
        $this->assertSoftDeleted('users', ['id_user' => $user->id_user]);
    }

    public function test_getname_attribute_mengembalikan_full_name(): void
    {
        $staff = new Staff(['full_name' => 'Citra Dewi']);

        $this->assertSame('Citra Dewi', $staff->name);
    }

    public function test_cascade_options_mengembalikan_employment_type_dan_staff_position_sesuai_academy_yang_diminta(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        EmploymentType::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Type A']);
        EmploymentType::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Type B']);

        $this->actingAsSuperAdmin();

        $response = $this->getJson(route('staff.cascade-options', ['id_academy' => $academyB->id_academy]));

        $response->assertOk();
        $names = collect($response->json('id_employment_type'))->pluck('label');
        $this->assertContains('Type B', $names);
        $this->assertNotContains('Type A', $names);
    }

    public function test_cascade_options_user_academy_biasa_mengabaikan_id_academy_dari_query(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        EmploymentType::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Type A']);
        EmploymentType::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Type B']);

        $this->actingAsOwner($academyA);

        $response = $this->getJson(route('staff.cascade-options', ['id_academy' => $academyB->id_academy]));

        $names = collect($response->json('id_employment_type'))->pluck('label');
        $this->assertContains('Type A', $names);
        $this->assertNotContains('Type B', $names);
    }
}
