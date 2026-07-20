<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Academy;


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

}