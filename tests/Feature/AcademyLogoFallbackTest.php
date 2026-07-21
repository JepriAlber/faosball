<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyLogoFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_academy_tanpa_logo_sidebar_menampilkan_nama_academy(): void
    {
        $academy = Academy::factory()->create(['name' => 'FC Garuda', 'logo_sidebar' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('FC Garuda');
    }

    public function test_academy_tanpa_logo_favicon_menampilkan_inisial(): void
    {
        $academy = Academy::factory()->create(['name' => 'FC Garuda', 'logo_favicon' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('FG');
    }

    public function test_academy_dengan_logo_sidebar_menampilkan_gambar_bukan_teks(): void
    {
        $academy = Academy::factory()->create([
            'name' => 'FC Garuda',
            'logo_sidebar' => 'academies/logo/FAKE-sidebar.png',
        ]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('storage/academies/logo/FAKE-sidebar.png', false);
        $response->assertDontSee('FC Garuda');
    }

    public function test_super_admin_selalu_pakai_logo_sistem_default(): void
    {
        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('KantinITSvg.svg', false);
    }
}
