<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SalaryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeStaffWithSalary(Academy $academy, float $salary, ?User $owner = null): Staff
    {
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_user' => $owner?->id_user,
        ]);

        // Eloquent Model tidak punya method tap() sendiri -- pakai helper
        // global tap() supaya closure benar-benar menerima $staff (Model),
        // bukan diam-diam diteruskan ke Query Builder lewat __call().
        return tap($staff, function (Staff $staff) use ($employmentType, $staffPosition, $salary) {
            $staff->contracts()->create([
                'id_academy' => $staff->id_academy,
                'id_employment_type' => $employmentType->id_employment_type,
                'id_staff_position' => $staffPosition->id_staff_position,
                'contract_code' => $staff->staff_code . '-C1',
                'start_date' => now(),
                'salary' => $salary,
                'status' => 'active',
            ]);
        });
    }

    protected function actingAsWithoutSalaryPermission(Academy $academy): User
    {
        Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Coach', 'guard_name' => 'web']);
        $role->syncPermissions(['staff.view']);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_user_tanpa_permission_tidak_bisa_lihat_gaji_staff_lain(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeStaffWithSalary($academy, 5000000);

        $user = $this->actingAsWithoutSalaryPermission($academy);

        $this->assertFalse($user->can('viewSalary', $staff));
    }

    public function test_user_tanpa_permission_tetap_bisa_lihat_gaji_sendiri(): void
    {
        $academy = Academy::factory()->create();
        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $staff = $this->makeStaffWithSalary($academy, 5000000, $owner);

        Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Coach', 'guard_name' => 'web']);
        $role->syncPermissions(['staff.view']);
        $owner->assignRole($role);

        $this->actingAs($owner);

        $this->assertTrue($owner->can('viewSalary', $staff));
    }

    public function test_user_dengan_permission_bisa_lihat_gaji_siapapun(): void
    {
        $academy = Academy::factory()->create();
        $staff = $this->makeStaffWithSalary($academy, 5000000);

        Permission::firstOrCreate(['name' => 'salary.view', 'guard_name' => 'web']);
        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Finance', 'guard_name' => 'web']);
        $role->syncPermissions(['salary.view']);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        $this->assertTrue($user->can('viewSalary', $staff));
    }
}
