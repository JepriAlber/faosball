<?php

namespace Tests\Unit;

use App\Support\Initials;
use PHPUnit\Framework\TestCase;

class InitialsTest extends TestCase
{
    public function test_dua_kata_ambil_huruf_pertama_masing_masing(): void
    {
        $this->assertSame('FG', Initials::from('FC Garuda'));
    }

    public function test_tiga_kata_tetap_ambil_dari_dua_kata_pertama(): void
    {
        $this->assertSame('AF', Initials::from('Akademi Futsal Merdeka'));
    }

    public function test_satu_kata_ambil_dua_huruf_pertama(): void
    {
        $this->assertSame('GA', Initials::from('Garuda'));
    }

    public function test_spasi_berlebih_tidak_mempengaruhi_hasil(): void
    {
        $this->assertSame('FG', Initials::from('  FC   Garuda  '));
    }

    public function test_selalu_huruf_besar(): void
    {
        $this->assertSame('FG', Initials::from('fc garuda'));
    }
}
