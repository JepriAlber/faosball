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

    public function test_upload_logo_persegi_menghasilkan_favicon_saja(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->image('logo.png', 300, 300);

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo' => $file]));

        $this->assertNotNull($academy->logo_favicon);
        Storage::disk('public')->assertExists($academy->logo_favicon);

        // logo_sidebar TIDAK ikut terisi lagi -- sejak issue8.md, field ini
        // punya upload+crop sendiri, bukan turunan dari logo persegi.
        $this->assertNull($academy->logo_sidebar);
    }

    public function test_upload_logo_sidebar_terpisah_dari_logo_persegi(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $file = UploadedFile::fake()->image('logo-sidebar.png', 980, 260);

        $academy = $svc->update($academy, $this->updatePayload($academy, ['logo_sidebar' => $file]));

        $this->assertNotNull($academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_sidebar);

        // logo (persegi) & logo_favicon TIDAK ikut terisi -- upload independen.
        $this->assertNull($academy->logo);
        $this->assertNull($academy->logo_favicon);
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

    public function test_ganti_logo_persegi_menghapus_favicon_lama_tanpa_sentuh_sidebar(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-lama.png', 300, 300),
            'logo_sidebar' => UploadedFile::fake()->image('sidebar.png', 980, 260),
        ]));

        $faviconLama = $academy->logo_favicon;
        $sidebarAwal = $academy->logo_sidebar;

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo-baru.png', 300, 300),
        ]));

        Storage::disk('public')->assertMissing($faviconLama);
        Storage::disk('public')->assertExists($academy->logo_favicon);

        // Mengganti logo persegi TIDAK BOLEH menyentuh logo_sidebar yang
        // tidak sedang diganti pada request yang sama.
        $this->assertSame($sidebarAwal, $academy->logo_sidebar);
        Storage::disk('public')->assertExists($academy->logo_sidebar);
    }

    public function test_ganti_logo_sidebar_menghapus_sidebar_lama_tanpa_sentuh_logo_persegi(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            'logo_sidebar' => UploadedFile::fake()->image('sidebar-lama.png', 980, 260),
        ]));

        $sidebarLama = $academy->logo_sidebar;
        $faviconAwal = $academy->logo_favicon;

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo_sidebar' => UploadedFile::fake()->image('sidebar-baru.png', 980, 260),
        ]));

        Storage::disk('public')->assertMissing($sidebarLama);
        Storage::disk('public')->assertExists($academy->logo_sidebar);

        // Mengganti logo_sidebar TIDAK BOLEH menyentuh logo persegi/favicon
        // yang tidak sedang diganti pada request yang sama.
        $this->assertSame($faviconAwal, $academy->logo_favicon);
        Storage::disk('public')->assertExists($academy->logo_favicon);
    }

    public function test_hapus_academy_menghapus_logo_dan_kedua_variannya(): void
    {
        $svc = app(AcademyManagementService::class);
        $academy = Academy::factory()->create();

        $academy = $svc->update($academy, $this->updatePayload($academy, [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            'logo_sidebar' => UploadedFile::fake()->image('sidebar.png', 980, 260),
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
            'logo_sidebar' => UploadedFile::fake()->image('sidebar.png', 980, 260),
        ]));

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $this->actingAs($owner);

        $svc = app(AcademyService::class);

        $this->assertStringContainsString($academy->logo_sidebar, $svc->sidebarLogoUrl());
        $this->assertStringContainsString($academy->logo_favicon, $svc->faviconUrl());
    }
}
