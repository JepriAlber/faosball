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

}