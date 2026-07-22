<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\EmploymentContract;
use App\Models\EmploymentType;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Services\EmploymentContractService;
use App\Services\StaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentContractTest extends TestCase
{
    use RefreshDatabase;

    protected function makePrereqs(Academy $academy): array
    {
        return [
            'employmentType' => EmploymentType::factory()->create(['id_academy' => $academy->id_academy]),
            'staffPosition' => StaffPosition::factory()->create(['id_academy' => $academy->id_academy]),
        ];
    }

    public function test_create_staff_otomatis_membuat_contract_pertama_berstatus_active(): void
    {
        $academy = Academy::factory()->create(['code' => 'FCY']);
        $prereqs = $this->makePrereqs($academy);

        $staff = app(StaffService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'full_name' => 'Dewi Lestari',
            'gender' => 'female',
            'birth_place' => 'Bandung',
            'birth_date' => '1992-05-05',
            'phone' => '081234567891',
        ]);

        $this->assertSame(1, $staff->contracts()->count());
        $this->assertSame('active', $staff->activeContract->status);
        $this->assertStringEndsWith('-C1', $staff->activeContract->contract_code);
    }

    public function test_tidak_bisa_buat_draft_kedua_untuk_staff_yang_sama(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makePrereqs($academy);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $service = app(EmploymentContractService::class);

        $data = [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now()->addMonth(),
        ];

        $service->createDraft($staff, $data);

        $this->expectException(\Exception::class);
        $service->createDraft($staff, $data);
    }

    public function test_activate_draft_otomatis_menutup_contract_active_lama(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makePrereqs($academy);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $oldActive = EmploymentContract::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'status' => 'active',
        ]);

        $service = app(EmploymentContractService::class);

        $draft = $service->createDraft($staff, [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now(),
        ]);

        $service->activate($draft);

        $this->assertSame('completed', $oldActive->fresh()->status);
        $this->assertSame('active', $draft->fresh()->status);
    }

    public function test_edit_ditolak_kalau_contract_bukan_draft(): void
    {
        $academy = Academy::factory()->create();
        $prereqs = $this->makePrereqs($academy);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $contract = EmploymentContract::factory()->create([
            'id_academy' => $academy->id_academy,
            'id_staff' => $staff->id_staff,
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'status' => 'active',
        ]);

        $this->expectException(\Exception::class);

        app(EmploymentContractService::class)->updateDraft($contract, [
            'id_employment_type' => $prereqs['employmentType']->id_employment_type,
            'id_staff_position' => $prereqs['staffPosition']->id_staff_position,
            'start_date' => now(),
        ]);
    }
}
