<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\Player;

class AuthStatsService
{
    /**
     * Statistik ringkas untuk panel branding halaman auth (login, register, dll).
     */
    public function snapshot(): array
    {
        return [
            'totalActivePlayers' => Player::where('status', true)->count(),
            'totalActiveAcademies' => Academy::where('status', true)->count(),
        ];
    }
}
