<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\PlayerPosition;
use App\Models\PlayerType;
use App\Models\Role;
use App\Models\User;
use App\Services\PlayerPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeAcademyUser(Academy $academy, array $permissions): User
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

    protected function makeSuperAdmin(array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Gate::before() mengizinkan role bernama "Super Admin" (bukan sekadar
        // id_academy null), jadi role-nya wajib di-assign supaya lolos
        // middleware permission.
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'id_academy' => null,
        ]);

        $user = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /**
     * INI SIFAT UTAMA MODULE INI: datanya GLOBAL.
     */
    public function test_master_posisi_sama_untuk_semua_academy(): void
    {
        PlayerPosition::factory()->create(['code' => 'GK', 'name' => 'Goalkeeper']);
        PlayerPosition::factory()->create(['code' => 'ST', 'name' => 'Striker']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $ownerA = $this->makeAcademyUser($academyA, ['player.view']);
        $ownerB = $this->makeAcademyUser($academyB, ['player.view']);

        $service = app(PlayerPositionService::class);

        $this->actingAs($ownerA);
        $daftarA = $service->selectable()->pluck('code')->sort()->values()->all();

        $this->actingAs($ownerB);
        $daftarB = $service->selectable()->pluck('code')->sort()->values()->all();

        // Dua academy berbeda HARUS melihat daftar yang sama persis.
        $this->assertSame(['GK', 'ST'], $daftarA);
        $this->assertSame($daftarA, $daftarB);
    }

    /**
     * CRUD master posisi khusus Super Admin.
     */
    public function test_owner_academy_tidak_bisa_membuka_master_posisi(): void
    {
        $academy = Academy::factory()->create();

        // Owner ini sengaja diberi permission player.* selengkap-lengkapnya,
        // untuk membuktikan yang menolak adalah player_position.view yang memang
        // tidak dia punya -- bukan sekadar "tidak punya izin apa-apa".
        $owner = $this->makeAcademyUser($academy, [
            'player.view', 'player.create', 'player.update', 'player.delete',
        ]);

        $this->actingAs($owner)
            ->get(route('player-positions.index'))
            ->assertForbidden();
    }

    public function test_super_admin_bisa_membuka_master_posisi(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($superAdmin)
            ->get(route('player-positions.index'))
            ->assertOk();
    }

    public function test_posisi_yang_dipakai_sebagai_posisi_utama_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();

        $utama = PlayerPosition::factory()->create(['code' => 'ST', 'name' => 'Striker']);

        Player::withoutGlobalScopes()->create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'id_primary_position' => $utama->id_player_position,
        ]);

        $this->expectException(\Exception::class);

        app(PlayerPositionService::class)->delete($utama);
    }

    /**
     * MENGUNCI BUG YANG PALING GAMPANG TERLEWAT. Lihat Bagian 4.2.
     */
    public function test_posisi_yang_dipakai_sebagai_posisi_kedua_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();

        $utama = PlayerPosition::factory()->create(['code' => 'ST', 'name' => 'Striker']);
        $kedua = PlayerPosition::factory()->create(['code' => 'CAM', 'name' => 'Attacking Midfielder']);

        Player::withoutGlobalScopes()->create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'id_primary_position' => $utama->id_player_position,
            'id_secondary_position' => $kedua->id_player_position,   // ← cuma dipakai sebagai posisi KEDUA
        ]);

        $this->expectException(\Exception::class);

        // Kalau delete() cuma mengecek primaryPlayers(), baris ini LOLOS
        // dan test-nya merah -- persis yang kita mau.
        app(PlayerPositionService::class)->delete($kedua);
    }

    protected function playerPayload(PlayerType $type, PlayerCategory $category, PlayerPosition $position, array $overrides = []): array
    {
        return array_merge([
            'id_player_type' => $type->id_player_type,
            'id_player_category' => $category->id_player_category,
            'id_primary_position' => $position->id_player_position,
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ], $overrides);
    }

    public function test_player_academy_manapun_boleh_memakai_posisi_yang_sama(): void
    {
        $position = PlayerPosition::factory()->create(['code' => 'ST']);

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeA = PlayerType::factory()->create(['id_academy' => $academyA->id_academy]);
        $categoryA = PlayerCategory::factory()->create(['id_academy' => $academyA->id_academy]);

        $typeB = PlayerType::factory()->create(['id_academy' => $academyB->id_academy]);
        $categoryB = PlayerCategory::factory()->create(['id_academy' => $academyB->id_academy]);

        $ownerA = $this->makeAcademyUser($academyA, ['player.view', 'player.create']);
        $ownerB = $this->makeAcademyUser($academyB, ['player.view', 'player.create']);

        $this->actingAs($ownerA)
            ->post(route('players.store'), $this->playerPayload($typeA, $categoryA, $position, ['name' => 'Player A']))
            ->assertSessionHasNoErrors();

        $this->actingAs($ownerB)
            ->post(route('players.store'), $this->playerPayload($typeB, $categoryB, $position, ['name' => 'Player B']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Player::withoutGlobalScopes()->where('id_primary_position', $position->id_player_position)->count());
    }

    public function test_posisi_kedua_tidak_boleh_sama_dengan_posisi_utama(): void
    {
        $academy = Academy::factory()->create();
        $position = PlayerPosition::factory()->create(['code' => 'ST']);
        $type = PlayerType::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        $owner = $this->makeAcademyUser($academy, ['player.view', 'player.create']);

        $this->actingAs($owner)
            ->post(route('players.store'), $this->playerPayload($type, $category, $position, [
                'id_secondary_position' => $position->id_player_position,
            ]))
            ->assertSessionHasErrors('id_secondary_position');
    }

    public function test_posisi_utama_wajib_posisi_kedua_opsional(): void
    {
        $academy = Academy::factory()->create();
        $position = PlayerPosition::factory()->create(['code' => 'ST']);
        $type = PlayerType::factory()->create(['id_academy' => $academy->id_academy]);
        $category = PlayerCategory::factory()->create(['id_academy' => $academy->id_academy]);

        $owner = $this->makeAcademyUser($academy, ['player.view', 'player.create']);

        // Tanpa posisi utama -> error.
        $payloadWithoutPrimary = $this->playerPayload($type, $category, $position);
        unset($payloadWithoutPrimary['id_primary_position']);

        $this->actingAs($owner)
            ->post(route('players.store'), $payloadWithoutPrimary)
            ->assertSessionHasErrors('id_primary_position');

        // Tanpa posisi kedua -> berhasil (opsional).
        $this->actingAs($owner)
            ->post(route('players.store'), $this->playerPayload($type, $category, $position))
            ->assertSessionHasNoErrors();
    }

    public function test_code_unik_dan_otomatis_huruf_besar(): void
    {
        PlayerPosition::factory()->create(['code' => 'CB']);

        $superAdmin = $this->makeSuperAdmin(['player_position.create']);

        $this->actingAs($superAdmin)
            ->post(route('player-positions.store'), [
                'code' => 'cb',
                'name' => 'Center Back Duplikat',
                'position_group' => 'Defender',
                'sort_order' => 11,
                'status' => 1,
            ])
            ->assertSessionHasErrors('code');
    }
}
