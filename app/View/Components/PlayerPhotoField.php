<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PlayerPhotoField extends Component
{
    public ?string $currentPhotoUrl;

    public function __construct(?string $currentPhotoUrl = null)
    {
        $this->currentPhotoUrl = $currentPhotoUrl;
    }

    public function render(): View
    {
        return view('components.player-photo-field');
    }
}
