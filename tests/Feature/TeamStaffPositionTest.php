<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\Staff;
use App\Models\TeamStaffPosition;
use App\Models\User;
use App\Services\TeamService;
use App\Services\TeamStaffPositionService;
use App\Services\TeamStaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamStaffPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_academy_baru_mendapat_team_staff_position_default_lengkap(): void
    {
        $academy = Academy::factory()->create();
        $templates = config('faos.team_staff_position_templates');

        app(TeamStaffPositionService::class)->createDefaultTeamStaffPositions($academy);

        $this->assertSame(
            count($templates),
            TeamStaffPosition::where('id_academy', $academy->id_academy)->count()
        );

        $this->assertTrue(
            TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'HC')->exists()
        );
    }

    public function test_dua_academy_boleh_punya_posisi_dengan_kode_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        TeamStaffPosition::factory()->create(['id_academy' => $academyA->id_academy, 'code' => 'HC']);
        TeamStaffPosition::factory()->create(['id_academy' => $academyB->id_academy, 'code' => 'HC']);

        $this->assertSame(2, TeamStaffPosition::withoutGlobalScopes()->where('code', 'HC')->count());
    }

    public function test_isolasi_url_akses_posisi_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $positionB = TeamStaffPosition::factory()->create(['id_academy' => $academyB->id_academy]);

        $ownerA = $this->makeUser($academyA, ['team_staff_position.update']);

        $this->actingAs($ownerA)
            ->get(route('team-staff-positions.edit', $positionB))
            ->assertNotFound();
    }

    public function test_posisi_yang_dipakai_team_staff_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();
        $position = TeamStaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        $team = app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);

        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        app(TeamStaffService::class)->assign($team, [
            'id_staff' => $staff->id_staff,
            'id_team_staff_position' => $position->id_team_staff_position,
        ]);

        $this->expectException(\Exception::class);

        app(TeamStaffPositionService::class)->delete($position);
    }

    public function test_crud_team_staff_position_lewat_http(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeUser($academy, [
            'team_staff_position.view', 'team_staff_position.create',
            'team_staff_position.update', 'team_staff_position.delete',
        ]);

        $this->actingAs($owner);

        $this->get(route('team-staff-positions.index'))->assertOk();

        $this->post(route('team-staff-positions.store'), [
            'code' => 'VC',
            'name' => 'Video Analyst',
            'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('team-staff-positions.index'));

        $position = TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'VC')->firstOrFail();

        $this->put(route('team-staff-positions.update', $position), [
            'code' => 'VC',
            'name' => 'Video Analyst Updated',
            'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('team-staff-positions.index'));

        $this->assertSame('Video Analyst Updated', $position->fresh()->name);

        $this->delete(route('team-staff-positions.destroy', $position))->assertRedirect(route('team-staff-positions.index'));

        $this->assertModelMissing($position);
    }
}
