<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_style_override_muncul_untuk_academy_dengan_primary_color(): void
    {
        $academy = Academy::factory()->create(['primary_color' => '#16a34a']);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('--color-brand-500: #16a34a', false);
    }

    public function test_style_override_tidak_muncul_kalau_primary_color_kosong(): void
    {
        $academy = Academy::factory()->create(['primary_color' => null]);
        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('--color-brand-500', false);
    }

    public function test_style_override_tidak_muncul_untuk_super_admin(): void
    {
        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);

        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('--color-brand-500', false);
    }
}
