<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyLogo extends Component
{
    public string $url;

    public function __construct(AcademyService $academyService, string $variant = 'sidebar')
    {
        $this->url = $variant === 'favicon'
            ? $academyService->faviconUrl()
            : $academyService->sidebarLogoUrl();
    }

    public function render(): View
    {
        return view('components.academy-logo');
    }
}
