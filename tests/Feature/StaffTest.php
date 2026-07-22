<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentType;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStaffPrereqs(Academy $academy): array
    {
        return [
            'employmentType' => EmploymentType::factory()->create(['id_academy' => $academy->id_academy]),
            'staffPosition' => StaffPosition::factory()->create(['id_academy' => $academy->id_academy]),
        ];
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
        $this->assertSame('active', $staff->status);
    }

    public function test_hapus_staff_ikut_menghapus_akun_terkait(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makeStaffPrereqs($academy);
        $user = User::factory()->create(['id_academy' => $academy->id_academy]);

        $staff = Staff::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_user' => $user->id_user,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
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
}
