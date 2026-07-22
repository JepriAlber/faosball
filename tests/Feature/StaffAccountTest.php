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

class StaffAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function actingAsOwner(Academy $academy): User
    {
        foreach (['staff.create', 'user.create', 'user.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', ['staff.create', 'user.create', 'user.update'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner);

        return $owner;
    }

    public function test_buat_akun_staff_dengan_role_dari_dropdown(): void
    {
        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);

        $coachRole = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Coach',
            'guard_name' => 'web',
        ]);

        $staffPosition = StaffPosition::factory()->create([
            'id_academy' => $academy->id_academy, 'role_id' => $coachRole->id,
        ]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
        ]);

        $this->actingAsOwner($academy);

        $response = $this->post(route('staff.account.store', $staff), [
            'email' => 'coach@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $coachRole->id,
        ]);

        $response->assertRedirect();
        $staff->refresh();
        $this->assertNotNull($staff->id_user);
        $this->assertTrue($staff->user->hasRole('Coach'));
    }

    public function test_role_dari_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $employmentType = EmploymentType::factory()->create(['id_academy' => $academyA->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academyA->id_academy]);

        $roleAcademyB = Role::create([
            'id_academy' => $academyB->id_academy,
            'name' => 'RoleB',
            'guard_name' => 'web',
        ]);

        $staff = Staff::factory()->create([
            'id_academy' => $academyA->id_academy,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
        ]);

        $this->actingAsOwner($academyA);

        $response = $this->post(route('staff.account.store', $staff), [
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $roleAcademyB->id,
        ]);

        $response->assertSessionHasErrors('role_id');
    }
}
