<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentContract;
use App\Models\EmploymentType;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Render penuh (bukan cuma php -l/view:cache) halaman Staff & Contract lewat
 * HTTP GET -- menangkap error runtime (properti/relasi undefined, variabel
 * yang tidak dikirim Controller, dst.) yang tidak kebaca cuma dari compile
 * Blade. Pengganti verifikasi browser manual untuk perubahan besar issue12.md.
 */
class StaffViewsSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function actingAsOwner(Academy $academy): User
    {
        foreach (['staff.view', 'staff.create', 'staff.update', 'salary.view', 'user.create', 'user.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create([
            'id_academy' => $academy->id_academy,
            'name' => 'Owner',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(Permission::whereIn('name', [
            'staff.view', 'staff.create', 'staff.update', 'salary.view', 'user.create', 'user.update',
        ])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner);

        return $owner;
    }

    public function test_halaman_index_create_staff_bisa_dirender(): void
    {
        $academy = Academy::factory()->create();
        $this->actingAsOwner($academy);

        $this->get(route('staff.index'))->assertOk();
        $this->get(route('staff.create'))->assertOk();
    }

    public function test_halaman_edit_show_staff_dengan_contract_bisa_dirender(): void
    {
        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);

        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        EmploymentContract::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
            'status' => 'active',
        ]);

        $this->actingAsOwner($academy);

        $this->get(route('staff.edit', $staff))->assertOk();
        $this->get(route('staff.show', $staff))->assertOk();
    }

    public function test_halaman_show_staff_tanpa_contract_bisa_dirender(): void
    {
        $academy = Academy::factory()->create();
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $this->get(route('staff.show', $staff))->assertOk();
        $this->get(route('staff.edit', $staff))->assertOk();
    }

    public function test_halaman_create_edit_contract_bisa_dirender(): void
    {
        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $draft = EmploymentContract::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $employmentType->id_employment_type,
            'id_staff_position' => $staffPosition->id_staff_position,
            'status' => 'draft',
        ]);

        $this->actingAsOwner($academy);

        $this->get(route('staff.contracts.create', $staff))->assertOk();
        $this->get(route('staff.contracts.edit', [$staff, $draft]))->assertOk();
    }

    public function test_halaman_account_create_bisa_dirender(): void
    {
        $academy = Academy::factory()->create();
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $this->get(route('staff.account.create', $staff))->assertOk();
    }
}
