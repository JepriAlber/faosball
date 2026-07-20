<?php

namespace App\Support;

class ColorRamp
{
    /**
     * Shade lebih terang dari base (500) -- rasio campuran ke arah PUTIH.
     * Angka lebih besar = lebih dekat ke putih.
     */
    private const WHITE_MIX = [
        '25' => 0.96,
        '50' => 0.92,
        '100' => 0.84,
        '200' => 0.68,
        '300' => 0.52,
        '400' => 0.26,
    ];

    /**
     * Shade lebih gelap dari base (500) -- rasio campuran ke arah HITAM.
     * Angka lebih besar = lebih dekat ke hitam.
     */
    private const BLACK_MIX = [
        '600' => 0.14,
        '700' => 0.28,
        '800' => 0.42,
        '900' => 0.56,
        '950' => 0.74,
    ];

    /**
     * Generate 12 shade dari 1 warna dasar, meniru struktur ramp Tailwind
     * (25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950).
     *
     * Pencampuran linear RGB sederhana ke arah putih/hitam -- BUKAN
     * perceptually-uniform seperti ramp biru bawaan yang di-tuning manual.
     * Cukup "layak dilihat" untuk warna arbitrary apa pun yang dipilih
     * academy. Kalau kualitas visualnya dirasa kurang di kemudian hari,
     * cukup ganti isi method mix()/konstanta di atas -- dampaknya terisolasi
     * di class ini saja.
     *
     * @return array<string,string> shade key ("25".."950") => hex "#rrggbb"
     */
    public static function generate(string $baseHex): array
    {
        [$r, $g, $b] = self::hexToRgb($baseHex);

        $ramp = [];

        foreach (self::WHITE_MIX as $shade => $ratio) {
            $ramp[$shade] = self::mix($r, $g, $b, 255, 255, 255, $ratio);
        }

        $ramp['500'] = self::rgbToHex($r, $g, $b);

        foreach (self::BLACK_MIX as $shade => $ratio) {
            $ramp[$shade] = self::mix($r, $g, $b, 0, 0, 0, $ratio);
        }

        return $ramp;
    }

    private static function mix(int $r, int $g, int $b, int $tr, int $tg, int $tb, float $ratio): string
    {
        return self::rgbToHex(
            (int) round($r + ($tr - $r) * $ratio),
            (int) round($g + ($tg - $g) * $ratio),
            (int) round($b + ($tb - $b) * $ratio),
        );
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, $r)),
            max(0, min(255, $g)),
            max(0, min(255, $b)),
        );
    }
}
