<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\PlayerCategory;
use App\Models\Role;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\SeasonService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SeasonTest extends TestCase
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

    public function test_dua_academy_boleh_punya_season_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        Season::factory()->create(['id_academy' => $academyA->id_academy, 'name' => '2026']);
        Season::factory()->create(['id_academy' => $academyB->id_academy, 'name' => '2026']);

        $this->assertSame(2, Season::withoutGlobalScopes()->where('name', '2026')->count());
    }

    public function test_isolasi_url_akses_season_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $seasonB = Season::factory()->create(['id_academy' => $academyB->id_academy]);

        $ownerA = $this->makeUser($academyA, ['season.update']);

        $this->actingAs($ownerA)
            ->get(route('seasons.edit', $seasonB))
            ->assertNotFound();
    }

    public function test_season_yang_dipakai_team_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();
        $season = Season::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        app(TeamService::class)->create([
            'id_academy' => $academy->id_academy,
            'id_season' => $season->id_season,
            'id_player_category' => $category->id_player_category,
            'name' => 'U15 A',
            'team_type' => 'regular',
        ]);

        $this->expectException(\Exception::class);

        app(SeasonService::class)->delete($season);
    }

    public function test_crud_season_lewat_http(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeUser($academy, ['season.view', 'season.create', 'season.update', 'season.delete']);

        $this->actingAs($owner);

        $this->get(route('seasons.index'))->assertOk();

        $this->post(route('seasons.store'), [
            'name' => '2026',
            'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('seasons.index'));

        $season = Season::where('id_academy', $academy->id_academy)->where('name', '2026')->firstOrFail();

        $this->put(route('seasons.update', $season), [
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('seasons.index'));

        $this->assertSame('2026-01-01', $season->fresh()->start_date->format('Y-m-d'));

        $this->delete(route('seasons.destroy', $season))->assertRedirect(route('seasons.index'));

        $this->assertModelMissing($season);
    }

    public function test_end_date_sebelum_start_date_ditolak(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeUser($academy, ['season.create']);

        $this->actingAs($owner)
            ->post(route('seasons.store'), [
                'name' => '2027',
                'start_date' => '2027-06-01',
                'end_date' => '2027-01-01',
                'status' => 1,
            ])
            ->assertSessionHasErrors('end_date');
    }
}
