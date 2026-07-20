<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Academy;
use App\Support\ColorRamp;


class AcademyService
{

    /**
     * Mendapatkan academy user aktif
     */
    public function current(): ?Academy
    {
        if (!Auth::check()) {
            return null;
        }

        return Auth::user()->academy;
    }


    /**
     * Mendapatkan ID academy aktif
     */
    public function currentId(): ?string
    {
        return Auth::user()?->id_academy;
    }


    /**
     * Mengecek apakah user adalah Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return Auth::user()?->id_academy === null;
    }


    /**
     * URL logo untuk slot "lebar" -- sidebar (expanded) + header mobile.
     * Fallback ke logo statis sistem kalau: Super Admin (tidak ada academy
     * aktif), academy belum upload logo, atau logo terakhir di-upload SVG
     * (di-skip dari proses resize, lihat AcademyManagementService::generateLogoVariants()).
     */
    public function sidebarLogoUrl(): string
    {
        $academy = $this->current();

        if ($academy && $academy->logo_sidebar) {
            return asset('storage/' . $academy->logo_sidebar);
        }

        return asset('assets/images/logo/KantinITSvg.svg');
    }


    /**
     * URL logo untuk slot "ikon persegi" -- sidebar (collapsed) + favicon
     * browser. Fallback sama seperti sidebarLogoUrl().
     */
    public function faviconUrl(): string
    {
        $academy = $this->current();

        if ($academy && $academy->logo_favicon) {
            return asset('storage/' . $academy->logo_favicon);
        }

        return asset('assets/images/logo/kantinit-favicon.png');
    }


    /**
     * Ramp 12 shade warna brand untuk academy AKTIF, siap dipakai sebagai
     * override CSS custom property. Return null kalau tidak perlu override
     * apa pun -- browser tetap pakai default biru dari variables.css:
     * - Super Admin (tidak ada academy aktif)
     * - Academy belum pernah set primary_color (kolom NULL)
     * - primary_color di database ternyata bukan format hex valid (data
     *   korup/lama) -- divalidasi ULANG di sini, bukan percaya kolom DB
     *   begitu saja walau sudah divalidasi Form Request saat disimpan.
     *   Lihat issue6.md Bagian 4.1.
     *
     * @return array<string,string>|null
     */
    public function brandColorVariables(): ?array
    {
        $academy = $this->current();

        if (!$academy || !$academy->primary_color) {
            return null;
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $academy->primary_color)) {
            return null;
        }

        return ColorRamp::generate($academy->primary_color);
    }

}