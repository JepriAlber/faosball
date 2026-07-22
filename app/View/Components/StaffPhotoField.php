<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StaffPhotoField extends Component
{
    public ?string $currentPhotoUrl;

    public function __construct(?string $currentPhotoUrl = null)
    {
        $this->currentPhotoUrl = $currentPhotoUrl;
    }

    public function render(): View
    {
        return view('components.staff-photo-field');
    }
}
