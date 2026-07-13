<?php

namespace App\View\Components;

use App\Services\AuthStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AuthSidebar extends Component
{
    public int $totalActivePlayers;
    public int $totalActiveAcademies;

    public function __construct(AuthStatsService $authStatsService)
    {
        $stats = $authStatsService->snapshot();

        $this->totalActivePlayers = $stats['totalActivePlayers'];
        $this->totalActiveAcademies = $stats['totalActiveAcademies'];
    }

    public function render(): View
    {
        return view('components.auth-sidebar');
    }
}
