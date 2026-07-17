<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Player;
use App\Models\PlayerCategory;
use App\Models\PlayerType;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\PlayerCategoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlayerCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie meng-cache peta permission. Tanpa ini, permission yang dibuat
        // di tengah test bisa terbaca basi.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function makeCategory(Academy $academy, string $name, int $minAge = 10, int $maxAge = 12): PlayerCategory
    {
        return PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => $name,
            'min_age' => $minAge,
            'max_age' => $maxAge,
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

    public function test_dua_academy_boleh_punya_kategori_dengan_nama_sama(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $this->makeCategory($academyA, 'U-12');
        $this->makeCategory($academyB, 'U-12');

        $this->assertSame(2, PlayerCategory::withoutGlobalScopes()->where('name', 'U-12')->count());
    }

    public function test_isolasi_url_akses_kategori_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $categoryB = $this->makeCategory($academyB, 'U-12');

        $ownerA = $this->makeUser($academyA, ['player_category.update']);

        $this->actingAs($ownerA)
            ->get(route('player-categories.edit', $categoryB))
            ->assertNotFound();
    }

    public function test_kategori_yang_dipakai_player_tidak_bisa_dihapus(): void
    {
        $academy = Academy::factory()->create();
        $category = $this->makeCategory($academy, 'U-12');
        $type = PlayerType::factory()->create(['id_academy' => $academy->id_academy]);
        $owner = $this->makeUser($academy, ['player.create']);

        $this->actingAs($owner);

        Player::create([
            'id_player_type' => $type->id_player_type,
            'id_player_category' => $category->id_player_category,
            'player_code' => 'TEST00001',
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
            'primary_position' => 'ST',
        ]);

        $this->expectException(\Exception::class);

        app(PlayerCategoryService::class)->delete($category);
    }

    /**
     * INI BATAS KEAMANAN UTAMA MODULE INI.
     */
    public function test_create_player_dengan_kategori_academy_lain_ditolak(): void
    {
        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $typeA = PlayerType::factory()->create(['id_academy' => $academyA->id_academy]);
        $categoryB = $this->makeCategory($academyB, 'U-12');

        $ownerA = $this->makeUser($academyA, ['player.view', 'player.create']);

        $this->actingAs($ownerA)
            ->post(route('players.store'), [
                'id_player_type' => $typeA->id_player_type,
                'id_player_category' => $categoryB->id_player_category,   // ← kategori academy lain
                'name' => 'Player Curang',
                'birth_date' => '2010-01-01',
                'gender' => 'male',
                'primary_position' => 'ST',
            ])
            ->assertSessionHasErrors('id_player_category');

        $this->assertSame(0, Player::withoutGlobalScopes()->count());
    }

    public function test_academy_baru_mendapat_kategori_default_lengkap(): void
    {
        $templates = config('faos.player_category_templates');

        $academy = app(AcademyManagementService::class)->create([
            'name' => 'Academy Category Template Test',
            'code' => 'ACT',
            'address' => 'Jl. Test No. 1',
        ]);

        $this->assertSame(
            count($templates),
            PlayerCategory::where('id_academy', $academy->id_academy)->count()
        );

        foreach ($templates as $name => $attributes) {

            $category = PlayerCategory::where('id_academy', $academy->id_academy)
                ->where('name', $name)
                ->first();

            $this->assertNotNull($category, "Player category \"{$name}\" tidak dibuat untuk academy baru.");

            $this->assertSame(
                $attributes['min_age'],
                $category->min_age,
                "min_age kategori \"{$name}\" tidak sesuai config('faos.player_category_templates')."
            );

            $this->assertSame(
                $attributes['max_age'],
                $category->max_age,
                "max_age kategori \"{$name}\" tidak sesuai config('faos.player_category_templates')."
            );
        }
    }

    /**
     * Saran kategori dari umur -- ini fitur khas module ini.
     */
    public function test_suggest_for_mengembalikan_kategori_sesuai_umur(): void
    {
        $academy = Academy::factory()->create();

        $u12 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
        ]);

        PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-15', 'min_age' => 13, 'max_age' => 15,
        ]);

        $service = app(PlayerCategoryService::class);

        // Umur 11 -> U-12
        $birthDate = Carbon::now()->subYears(11)->subMonths(2);
        $this->assertSame(
            $u12->id_player_category,
            $service->suggestFor($birthDate, $academy->id_academy)?->id_player_category
        );

        // Umur 30 -> tidak ada yang cocok
        $this->assertNull(
            $service->suggestFor(Carbon::now()->subYears(30), $academy->id_academy)
        );

        // Tanpa tanggal lahir -> tidak menebak
        $this->assertNull($service->suggestFor(null, $academy->id_academy));
    }

    /**
     * Mengunci orderBy('min_age') di suggestFor(). Lihat issue2.md Bagian 4.3.
     */
    public function test_suggest_for_deterministik_saat_rentang_tumpang_tindih(): void
    {
        $academy = Academy::factory()->create();

        // Sengaja dibuat TERBALIK urutan insert-nya, supaya kalau orderBy
        // hilang, test ini gampang merah.
        PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-13', 'min_age' => 12, 'max_age' => 13,
        ]);

        $u12 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-12', 'min_age' => 10, 'max_age' => 12,
        ]);

        $service = app(PlayerCategoryService::class);
        $birthDate = Carbon::now()->subYears(12)->subMonths(1);   // umur 12 -> cocok DUA-DUANYA

        // min_age terkecil yang menang, dan hasilnya konsisten tiap dipanggil.
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame(
                $u12->id_player_category,
                $service->suggestFor($birthDate, $academy->id_academy)?->id_player_category
            );
        }
    }

    /**
     * INI MENGUNCI KEPUTUSAN PALING PENTING DI MODULE INI.
     * Pemain berbakat boleh "main naik kelas". Lihat issue2.md Bagian 4.2.
     */
    public function test_player_boleh_ditempatkan_di_kategori_di_luar_umurnya(): void
    {
        $academy = Academy::factory()->create();

        $type = PlayerType::factory()->create(['id_academy' => $academy->id_academy]);

        $u17 = PlayerCategory::factory()->create([
            'id_academy' => $academy->id_academy,
            'name' => 'U-17', 'min_age' => 16, 'max_age' => 17,
        ]);

        $owner = $this->makeUser($academy, ['player.view', 'player.create']);

        $this->actingAs($owner)
            ->post(route('players.store'), [
                'id_player_type' => $type->id_player_type,
                'id_player_category' => $u17->id_player_category,   // umur 11 -> kategori U-17
                'name' => 'Pemain Berbakat',
                'birth_date' => Carbon::now()->subYears(11)->format('Y-m-d'),
                'gender' => 'male',
                'primary_position' => 'ST',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('players.index'));

        $this->assertSame(
            $u17->id_player_category,
            Player::withoutGlobalScopes()->where('name', 'Pemain Berbakat')->first()->id_player_category
        );
    }

    public function test_max_age_lebih_kecil_dari_min_age_ditolak(): void
    {
        $academy = Academy::factory()->create();
        $owner = $this->makeUser($academy, ['player_category.create']);

        $this->actingAs($owner)
            ->post(route('player-categories.store'), [
                'name' => 'U-Aneh',
                'min_age' => 15,
                'max_age' => 12,
                'status' => 1,
            ])
            ->assertSessionHasErrors('max_age');
    }
}
