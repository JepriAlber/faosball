<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\StaffPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffPositionTest extends TestCase
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

    protected function makeSuperAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'staff_position.view', 'guard_name' => 'web']);

        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['id_academy' => null, 'status' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_default_staff_position_terpetakan_ke_role_yang_benar(): void
    {
        $academy = app(AcademyManagementService::class)->create([
            'name' => 'FC Test', 'code' => 'FCTEST', 'phone' => '08123456', 'email' => 'fc@test.com',
            'address' => 'Jl. Test', 'tagline' => 'Test', 'subscription_type' => 'monthly',
            'subscription_fee' => 100000, 'subscription_started_at' => now(), 'subscription_ends_at' => now()->addMonth(),
            'primary_color' => '#465fff',
        ]);

        $positions = StaffPosition::where('id_academy', $academy->id_academy)
            ->with('role')
            ->get()
            ->pluck('role.name', 'name');

        $this->assertSame('Coach', $positions['Head Coach']);
        $this->assertSame('Finance', $positions['Finance Manager']);
        $this->assertSame('Owner', $positions['Academy Director']);
        $this->assertSame('Staff', $positions['Admin']);
    }

    public function test_role_id_boleh_null(): void
    {
        $academy = Academy::factory()->create();

        $owner = $this->makeUser($academy, ['staff_position.create']);
        $this->actingAs($owner);

        $position = app(StaffPositionService::class)->create([
            'code' => 'TST', 'name' => 'Test Position', 'is_coach' => false, 'status' => true,
        ]);

        $this->assertNull($position->role_id);
    }

    public function test_role_dari_academy_lain_ditolak_form_request(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $roleAcademyB = Role::create([
            'id_academy' => $academyB->id_academy,
            'name' => 'RoleB',
            'guard_name' => 'web',
        ]);

        $owner = $this->makeUser($academyA, ['staff_position.create']);
        $this->actingAs($owner);

        $response = $this->post(route('staff-positions.store'), [
            'code' => 'TST', 'name' => 'Test Position', 'is_coach' => 0, 'status' => 1,
            'role_id' => $roleAcademyB->id,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_filter_search_by_name(): void
    {
        $academy = Academy::factory()->create();

        StaffPosition::factory()->create(['id_academy' => $academy->id_academy, 'code' => 'VOL', 'name' => 'Volunteer Coach']);
        StaffPosition::factory()->create(['id_academy' => $academy->id_academy, 'code' => 'FRE', 'name' => 'Freelance Analyst']);

        $user = $this->makeUser($academy, ['staff_position.view']);

        $response = $this->actingAs($user)->get(route('staff-positions.index', ['search' => 'Volunteer']));

        $response->assertOk();
        $response->assertSee('Volunteer Coach');
        $response->assertDontSee('Freelance Analyst');
    }

    public function test_filter_status_active_dan_inactive(): void
    {
        $academy = Academy::factory()->create();

        StaffPosition::factory()->create(['id_academy' => $academy->id_academy, 'code' => 'VOL', 'name' => 'Volunteer Coach', 'status' => true]);
        StaffPosition::factory()->create(['id_academy' => $academy->id_academy, 'code' => 'FRE', 'name' => 'Freelance Analyst', 'status' => false]);

        $user = $this->makeUser($academy, ['staff_position.view']);

        $activeOnly = $this->actingAs($user)->get(route('staff-positions.index', ['status' => 'active']));
        $activeOnly->assertSee('Volunteer Coach');
        $activeOnly->assertDontSee('Freelance Analyst');

        $inactiveOnly = $this->actingAs($user)->get(route('staff-positions.index', ['status' => 'inactive']));
        $inactiveOnly->assertSee('Freelance Analyst');
        $inactiveOnly->assertDontSee('Volunteer Coach');
    }

    public function test_filter_academy_hanya_berlaku_untuk_super_admin(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        StaffPosition::factory()->create(['id_academy' => $academyA->id_academy, 'code' => 'VOL', 'name' => 'Volunteer Coach']);
        StaffPosition::factory()->create(['id_academy' => $academyB->id_academy, 'code' => 'FRE', 'name' => 'Freelance Analyst']);

        // User academy biasa -- dropdown filter Academy tidak ditampilkan sama
        // sekali (tidak pernah bisa mengirim id_academy lewat UI). AcademyScope
        // tetap membatasinya ke academy sendiri seperti biasa.
        $ownerA = $this->makeUser($academyA, ['staff_position.view']);

        $responseOwner = $this->actingAs($ownerA)->get(route('staff-positions.index'));
        $responseOwner->assertOk();
        $responseOwner->assertDontSee('name="id_academy"', false);
        $responseOwner->assertSee('Volunteer Coach');
        $responseOwner->assertDontSee('Freelance Analyst');

        // Super Admin -- dropdown Academy muncul, dan filter id_academy
        // benar-benar mempersempit ke academy yang dipilih.
        $superAdmin = $this->makeSuperAdmin();

        $responseSuperAdmin = $this->actingAs($superAdmin)->get(route('staff-positions.index', ['id_academy' => $academyB->id_academy]));
        $responseSuperAdmin->assertOk();
        $responseSuperAdmin->assertSee('name="id_academy"', false);
        $responseSuperAdmin->assertSee('Freelance Analyst');
        $responseSuperAdmin->assertDontSee('Volunteer Coach');
    }

    public function test_cascade_options_mengembalikan_role_sesuai_academy_yang_diminta(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        Role::create(['id_academy' => $academyA->id_academy, 'name' => 'Role A', 'guard_name' => 'web']);
        Role::create(['id_academy' => $academyB->id_academy, 'name' => 'Role B', 'guard_name' => 'web']);

        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)
            ->getJson(route('staff-positions.cascade-options', ['id_academy' => $academyB->id_academy]));

        $response->assertOk();
        $names = collect($response->json('role_id'))->pluck('label');
        $this->assertContains('Role B', $names);
        $this->assertNotContains('Role A', $names);
    }

    public function test_cascade_options_user_academy_biasa_mengabaikan_id_academy_dari_query(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        Role::create(['id_academy' => $academyA->id_academy, 'name' => 'Role A', 'guard_name' => 'web']);
        Role::create(['id_academy' => $academyB->id_academy, 'name' => 'Role B', 'guard_name' => 'web']);

        $owner = $this->makeUser($academyA, ['staff_position.create']);

        $response = $this->actingAs($owner)
            ->getJson(route('staff-positions.cascade-options', ['id_academy' => $academyB->id_academy]));

        $names = collect($response->json('role_id'))->pluck('label');
        $this->assertContains('Role A', $names);
        $this->assertNotContains('Role B', $names);
    }
}
