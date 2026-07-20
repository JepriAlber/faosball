<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_indonesia_untuk_user_baru(): void
    {
        $user = User::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('id', app()->getLocale());
    }

    public function test_switch_locale_menyimpan_ke_kolom_user_dan_langsung_berlaku(): void
    {
        $user = User::factory()->create(['status' => true]);

        $response = $this->actingAs($user)->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $this->assertSame('en', $user->fresh()->locale);

        $this->actingAs($user)->get(route('dashboard'));
        $this->assertSame('en', app()->getLocale());
    }

    public function test_switch_locale_untuk_guest_disimpan_ke_session(): void
    {
        $response = $this->get(route('locale.switch', 'en'));

        $response->assertRedirect();
        $this->assertSame('en', session('locale'));
    }

    public function test_locale_tidak_terdaftar_ditolak_404(): void
    {
        $response = $this->get(route('locale.switch', 'fr'));

        $response->assertNotFound();
    }

    public function test_locale_invalid_di_kolom_user_fallback_ke_default(): void
    {
        $user = User::factory()->create(['status' => true, 'locale' => 'fr']);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame('id', app()->getLocale());
    }
}
