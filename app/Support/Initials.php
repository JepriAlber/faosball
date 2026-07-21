<?php

namespace App\Support;

class Initials
{
    /**
     * Ambil 1-2 huruf inisial dari nama academy, dipakai badge fallback
     * saat logo_favicon belum diupload:
     * - 2+ kata -> huruf pertama dari 2 kata PERTAMA ("FC Garuda Muda" -> "FG")
     * - 1 kata  -> 2 huruf pertama kata itu ("Garuda" -> "GA")
     * - kosong  -> "?" (secara praktik tidak pernah kejadian karena `name`
     *   wajib diisi saat Academy dibuat, tapi badge tidak boleh pernah
     *   menampilkan string kosong)
     */
    public static function from(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '?';
        }

        if (count($words) === 1) {
            return strtoupper(mb_substr($words[0], 0, 2));
        }

        return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
}
