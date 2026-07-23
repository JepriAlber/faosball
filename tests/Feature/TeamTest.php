<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\Staff;
use App\Models\Team;
use App\Models\TeamStaffPosition;
use App\Models\User;
use App\Services\TeamPlayerService;
use App\Services\TeamService;
use App\Services\TeamStaffService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function actingAsOwner(Academy $academy): User
    {
        foreach (['team.view', 'team.create', 'team.update', 'team.delete'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['team.view', 'team.create', 'team.update', 'team.delete'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);
        $this->actingAs($owner);

        return $owner;
    }

    protected function makeTeam(Academy $academy): Team
    {
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        return app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);
    }

    protected function makePlayer(Academy $academy, string $name = 'Test Player'): Player
    {
        return Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST' . random_int(10000, 99999),
            'name' => $name,
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);
    }

    public function test_generate_kode_team_otomatis(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);

        $this->assertStringStartsWith('TM', $team->code);
    }

    public function test_nomor_punggung_tidak_boleh_duplikat_di_tim_yang_sama(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $playerA = $this->makePlayer($academy, 'Player A');
        $playerB = $this->makePlayer($academy, 'Player B');

        $service = app(TeamPlayerService::class);
        $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10]);

        $this->expectException(\Exception::class);
        $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 10]);
    }

    public function test_set_captain_baru_otomatis_melepas_captain_lama(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $playerA = $this->makePlayer($academy, 'Player A');
        $playerB = $this->makePlayer($academy, 'Player B');

        $service = app(TeamPlayerService::class);
        $tpA = $service->assign($team, ['id_player' => $playerA->id_player, 'jersey_number' => 10, 'is_captain' => true]);
        $service->assign($team, ['id_player' => $playerB->id_player, 'jersey_number' => 7, 'is_captain' => true]);

        $this->assertFalse($tpA->fresh()->is_captain);
    }

    public function test_assign_head_coach_baru_otomatis_mengeluarkan_head_coach_lama(): void
    {
        $academy = Academy::factory()->create();
        app(\App\Services\TeamStaffPositionService::class)->createDefaultTeamStaffPositions($academy);
        $team = $this->makeTeam($academy);

        $staffA = Staff::factory()->create(['id_academy' => $academy->id_academy]);
        $staffB = Staff::factory()->create(['id_academy' => $academy->id_academy]);
        $headCoach = TeamStaffPosition::where('id_academy', $academy->id_academy)->where('code', 'HC')->first();

        $service = app(TeamStaffService::class);
        $tsA = $service->assign($team, ['id_staff' => $staffA->id_staff, 'id_team_staff_position' => $headCoach->id_team_staff_position]);
        $service->assign($team, ['id_staff' => $staffB->id_staff, 'id_team_staff_position' => $headCoach->id_team_staff_position]);

        $this->assertNotNull($tsA->fresh()->leave_date);
    }

    public function test_delete_team_ditolak_kalau_masih_ada_player_aktif(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $player = $this->makePlayer($academy);

        app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

        $this->expectException(\Exception::class);
        app(TeamService::class)->delete($team);
    }

    public function test_delete_team_sukses_setelah_semua_anggota_keluar_dan_soft_delete(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);
        $player = $this->makePlayer($academy);

        $teamPlayerService = app(TeamPlayerService::class);
        $tp = $teamPlayerService->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);
        $teamPlayerService->leave($tp);

        app(TeamService::class)->delete($team);

        $this->assertSoftDeleted('teams', ['id_team' => $team->id_team]);
    }

    public function test_halaman_index_dan_detail_team_bisa_diakses(): void
    {
        $academy = Academy::factory()->create();
        $team = $this->makeTeam($academy);

        $this->actingAsOwner($academy);

        $this->get(route('teams.index'))->assertOk();
        $this->get(route('teams.show', $team))->assertOk();
    }
}
