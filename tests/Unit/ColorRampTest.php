<?php

namespace Tests\Unit;

use App\Support\ColorRamp;
use PHPUnit\Framework\TestCase;

class ColorRampTest extends TestCase
{
    public function test_menghasilkan_12_shade(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertCount(12, $ramp);
        $this->assertArrayHasKey('500', $ramp);
        $this->assertArrayHasKey('25', $ramp);
        $this->assertArrayHasKey('950', $ramp);
    }

    public function test_shade_500_sama_persis_dengan_input(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertSame('#465fff', $ramp['500']);
    }

    public function test_shade_lebih_terang_dari_500_ke_arah_25(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        // Jumlah RGB shade 25 harus lebih besar (lebih terang/mendekati
        // putih) dibanding 400, dan 400 lebih besar dari 500.
        $this->assertGreaterThan($this->brightness($ramp['400']), $this->brightness($ramp['25']));
        $this->assertGreaterThan($this->brightness($ramp['500']), $this->brightness($ramp['400']));
    }

    public function test_shade_lebih_gelap_dari_500_ke_arah_950(): void
    {
        $ramp = ColorRamp::generate('#465fff');

        $this->assertLessThan($this->brightness($ramp['500']), $this->brightness($ramp['600']));
        $this->assertLessThan($this->brightness($ramp['600']), $this->brightness($ramp['950']));
    }

    public function test_input_hitam_tidak_error_dan_tetap_valid_hex(): void
    {
        $ramp = ColorRamp::generate('#000000');

        $this->assertSame('#000000', $ramp['500']);
        $this->assertSame('#000000', $ramp['950']); // mixing hitam ke hitam tetap hitam
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $ramp['25']);
    }

    private function brightness(string $hex): int
    {
        $hex = ltrim($hex, '#');

        return hexdec(substr($hex, 0, 2)) + hexdec(substr($hex, 2, 2)) + hexdec(substr($hex, 4, 2));
    }
}
