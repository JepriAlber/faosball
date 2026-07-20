<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LogoUploadField extends Component
{
    public ?string $currentLogoUrl;

    public function __construct(?string $currentLogoUrl = null)
    {
        $this->currentLogoUrl = $currentLogoUrl;
    }

    public function render(): View
    {
        return view('components.logo-upload-field');
    }
}
