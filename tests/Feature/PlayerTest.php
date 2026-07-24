<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Services\TeamPlayerService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'player.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'team.view', 'guard_name' => 'web']);

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['player.view', 'team.view'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);
        $this->actingAs($owner);

        return $owner;
    }

    public function test_halaman_show_player_menampilkan_tab_teams(): void
    {
        $academy = Academy::factory()->create();
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        $team = app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);

        $player = Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        app(TeamPlayerService::class)->assign($team, ['id_player' => $player->id_player, 'jersey_number' => 10]);

        $this->actingAsOwner($academy);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('U15 A');
    }

    public function test_halaman_show_player_tanpa_tim_menampilkan_empty_state(): void
    {
        $academy = Academy::factory()->create();

        $player = Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00002',
            'name' => 'Player Tanpa Tim',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        $this->actingAsOwner($academy);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Belum menjadi anggota tim manapun.');
    }
}
