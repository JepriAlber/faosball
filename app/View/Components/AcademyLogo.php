<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyLogo extends Component
{
    public ?string $url;
    public ?string $fallbackText;
    public bool $isFavicon;

    public function __construct(AcademyService $academyService, string $variant = 'sidebar')
    {
        $this->isFavicon = $variant === 'favicon';

        $hasOwnLogo = $academyService->isSuperAdmin()
            || ($this->isFavicon ? $academyService->hasOwnFaviconLogo() : $academyService->hasOwnSidebarLogo());

        $this->url = $hasOwnLogo
            ? ($this->isFavicon ? $academyService->faviconUrl() : $academyService->sidebarLogoUrl())
            : null;

        $this->fallbackText = $hasOwnLogo
            ? null
            : ($this->isFavicon ? $academyService->faviconFallbackInitials() : $academyService->sidebarFallbackName());
    }

    public function render(): View
    {
        return view('components.academy-logo');
    }
}
