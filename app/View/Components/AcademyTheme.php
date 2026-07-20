<?php

namespace App\View\Components;

use App\Services\AcademyService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AcademyTheme extends Component
{
    public ?array $ramp;

    public function __construct(AcademyService $academyService)
    {
        $this->ramp = $academyService->brandColorVariables();
    }

    public function render(): View
    {
        return view('components.academy-theme');
    }
}
