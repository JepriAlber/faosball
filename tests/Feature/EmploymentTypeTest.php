<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\User;
use App\Services\EmploymentTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmploymentTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeUser(Academy $academy, array $permissions): User
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

    public function test_create_default_employment_types_membuat_6_type_dari_config(): void
    {
        $academy = Academy::factory()->create();

        app(EmploymentTypeService::class)->createDefaultEmploymentTypes($academy);

        // Urutan row TIDAK dijamin (id_employment_type UUID acak, bukan
        // ordered) -- bandingkan sebagai set, bukan array berurutan.
        $this->assertEqualsCanonicalizing(
            array_keys(config('faos.employment_type_templates')),
            EmploymentType::where('id_academy', $academy->id_academy)->pluck('name')->all()
        );
    }

    public function test_nama_duplikat_di_academy_yang_sama_ditolak(): void
    {
        $academy = Academy::factory()->create();

        EmploymentType::factory()->create(['id_academy' => $academy->id_academy, 'name' => 'Permanent']);

        // FormRequest yang menegakkan unique -- di sini langsung cek DB
        // constraint-nya (unique index) sebagai jaring pengaman terakhir.
        $this->expectException(\Illuminate\Database\QueryException::class);

        EmploymentType::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Permanent',
            'status' => true,
        ]);
    }

    public function test_academy_lain_tidak_bisa_lihat_employment_type_academy_lain(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        // "Permanent"/"Contract"/"Intern" sengaja dihindari -- kata itu juga
        // muncul di teks statis card-description ("Manajemen jenis pekerjaan
        // staff (Permanent, Contract, Intern, dsb)..."), jadi assertDontSee
        // akan false-positive kalau dipakai sebagai nama data uji.
        EmploymentType::factory()->create(['id_academy' => $academyA->id_academy, 'name' => 'Volunteer']);
        EmploymentType::factory()->create(['id_academy' => $academyB->id_academy, 'name' => 'Freelance']);

        $user = $this->makeUser($academyB, ['employment_type.view']);
        $this->actingAs($user);

        $response = $this->get(route('employment-types.index'));

        $response->assertOk();
        $response->assertDontSee('Volunteer');
        $response->assertSee('Freelance');
    }
}
