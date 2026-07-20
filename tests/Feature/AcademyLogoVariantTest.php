<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Role;
use App\Models\User;
use App\Services\AcademyManagementService;
use App\Services\AcademyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyLogoVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function updatePayload(Academy $academy, array $overrides = []): array
    {
        return array_merge([
            'name' => $academy->name,
            'code' => $academy->code,
            'phone' => $academy->phone,
            'email' => $academy->email,
            'address' => $academy->address,
            'tagline' => $academy->tagline,
            'status' => true,
            'subscription_type' => 'monthly',
            'subscription_fee' => 100000,
            'subscription_started_at' => now()->toDateString(),
            'subscription_ends_at' => now()->addMonth()->toDateString(),
        ], $overrides);
    }

    public function test_upload_logo_raster_menghasilkan_dua_varian(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->image('logo.png', 300, 300);

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo' => $file]));

        $this->assertNotNull($academy->logo_sidebar);
        $this->assertNotNull($academy->logo_favicon);
        Storage::disk('public')->assertExists($academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_favicon);
    }

    public function test_upload_logo_svg_di_skip_bukan_error(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml');

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo' => $file]));

        $this->assertNull($academy->logo_sidebar);
        $this->assertNull($academy->logo_favicon);
        $this->assertNotNull($academy->logo);
        Storage::disk('public')->assertExists($academy->logo);
    }

    public function test_ganti_logo_menghapus_varian_lama(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-lama.png', 300, 300),
        ]));

        $sidebarLama = $academy->logo_sidebar;
        $faviconLama = $academy->logo_favicon;

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-baru.png', 300, 300),
        ]));

        Storage::disk('public')->assertMissing($sidebarLama);
        Storage::disk('public')->assertMissing($faviconLama);
        Storage::disk('public')->assertExists($academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_favicon);
    }

    public function test_hapus_academy_menghapus_logo_dan_variannya(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ]));

        $logo = $academy->logo;
        $sidebar = $academy->logo_sidebar;
        $favicon = $academy->logo_favicon;

        $svc->delete($academy);

        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertMissing($sidebar);
        Storage::disk('public')->assertMissing($favicon);
    }

    public function test_academy_service_fallback_ke_logo_statis_untuk_super_admin(): void
    {
        $role = Role::firstOrCreate([
            'id_academy' => null,
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $superAdmin = User::factory()->create(['id_academy' => null, 'status' => true]);
        $superAdmin->assignRole($role);

        $this->actingAs($superAdmin);

        $svc = app(AcademyService::class);

        $this->assertStringContainsString('KantinITSvg.svg', $svc->sidebarLogoUrl());
        $this->assertStringContainsString('kantinit-favicon.png', $svc->faviconUrl());
    }

    public function test_academy_service_pakai_logo_academy_saat_sudah_ada_varian(): void
    {
        $academyManagementService = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $academyManagementService->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
        ]));

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $this->actingAs($owner);

        $svc = app(AcademyService::class);

        $this->assertStringContainsString($academy->logo_sidebar, $svc->sidebarLogoUrl());
        $this->assertStringContainsString($academy->logo_favicon, $svc->faviconUrl());
    }
}
