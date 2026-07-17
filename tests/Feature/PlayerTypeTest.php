<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerType;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\PlayerTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeType(Academy $academy, string $name, bool $isBillable = true): PlayerType
    {
        return PlayerType::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => $name,
            'is_billable' => $isBillable,
        ]);
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

    public function test_dua_academy_boleh_punya_type_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeType($academyA, 'Reguler');
        $this->makeType($academyB, 'Reguler');

        $this->assertSame(2, PlayerType::withoutGlobalScopes()->where('name', 'Reguler')->count());
    }

    public function test_satu_academy_tidak_boleh_punya_dua_type_dengan_nama_sama(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeUser($academy, ['player_type.create']);

        $this->actingAs($owner)
            ->post(route('player-types.store'), [
                'name' => 'Reguler',
                'is_billable' => 1,
                'status' => 1,
            ])
            ->assertSessionDoesntHaveErrors('name');

        $this->actingAs($owner)
            ->post(route('player-types.store'), [
                'name' => 'Reguler',
                'is_billable' => 1,
                'status' => 1,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_isolasi_url_akses_type_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeB = $this->makeType($academyB, 'Reguler');

        // Owner A wajib punya player_type.update supaya yang diuji benar-benar
        // scope-nya (AcademyScope), bukan middleware permission.
        $ownerA = $this->makeUser($academyA, ['player_type.update']);

        $this->actingAs($ownerA)
            ->get(route('player-types.edit', $typeB))
            ->assertNotFound();
    }

    public function test_isolasi_daftar_paginate_tidak_memuat_type_academy_lain(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeA = $this->makeType($academyA, 'Reguler');
        $typeB = $this->makeType($academyB, 'Reguler');

        $ownerA = $this->makeUser($academyA, []);

        $this->actingAs($ownerA);

        $types = app(PlayerTypeService::class)->paginate();

        $this->assertTrue($types->pluck('id_player_type')->contains($typeA->id_player_type));
        $this->assertFalse($types->pluck('id_player_type')->contains($typeB->id_player_type));
    }

    public function test_type_yang_dipakai_player_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();
        $type = $this->makeType($academy, 'Reguler');
        $owner = $this->makeUser($academy, ['player.create']);

        $this->actingAs($owner);

        Player::create([
            'id_player_type' => $type->id_player_type,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);

        $this->expectException(\Exception::class);

        app(PlayerTypeService::class)->delete($type);
    }

    /**
     * INI BATAS KEAMANAN UTAMA MODULE INI.
     */
    public function test_create_player_dengan_type_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeB = $this->makeType($academyB, 'Reguler');

        $ownerA = $this->makeUser($academyA, ['player.view', 'player.create']);

        $this->actingAs($ownerA)
            ->post(route('players.store'), [
                'id_player_type' => $typeB->id_player_type,   // ← type academy lain
                'name' => 'Player Curang',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
            ])
            ->assertSessionHasErrors('id_player_type');

        $this->assertSame(0, Player::withoutGlobalScopes()->count());
    }

    public function test_super_admin_type_harus_milik_academy_yang_dipilih(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeB = $this->makeType($academyB, 'Reguler');

        Permission::firstOrCreate(['name' => 'player.create', 'guard_name' => 'web']);

        // Gate::before() mengizinkan role bernama "Super Admin" (bukan sekadar
        // id_academy null), jadi role-nya wajib di-assign supaya lolos
        // middleware permission:player.create.
        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'id_academy' => null,
        ]);

        $superAdmin = User::factory()->create([
            'id_academy' => null,
            'status' => true,
        ]);

        $superAdmin->assignRole($superAdminRole);

        $this->actingAs($superAdmin)
            ->post(route('players.store'), [
                'id_academy' => $academyA->id_academy,
                'id_player_type' => $typeB->id_player_type,
                'name' => 'Player Curang Super Admin',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
            ])
            ->assertSessionHasErrors('id_player_type');

        $this->assertSame(0, Player::withoutGlobalScopes()->count());
    }

    public function test_academy_baru_mendapat_type_default_lengkap(): void
    {
        $templates = config('faos.player_type_templates');

        $academy = app(AcademyManagementService::class)->create([
            'name' => 'Academy Type Template Test',
            'code' => 'ATT',
            'address' => 'Jl. Test No. 1',
        ]);

        $this->assertSame(
            count($templates),
            PlayerType::where('id_academy', $academy->id_academy)->count()
        );

        foreach ($templates as $name => $attributes) {

            $type = PlayerType::where('id_academy', $academy->id_academy)
                ->where('name', $name)
                ->first();

            $this->assertNotNull($type, "Player type \"{$name}\" tidak dibuat untuk academy baru.");

            $this->assertSame(
                $attributes['is_billable'],
                $type->is_billable,
                "is_billable type \"{$name}\" tidak sesuai config('faos.player_type_templates')."
            );
        }
    }
}
