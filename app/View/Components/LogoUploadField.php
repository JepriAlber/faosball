<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LogoUploadField extends Component
{
    public ?string $currentLogoUrl;
    public string $name;
    public string $label;
    public string $helpText;
    public string $cropTitle;
    public string $cropDescription;
    public float $aspectRatio;
    public int $outputWidth;
    public int $outputHeight;
    public string $previewClass;

    public function __construct(
        ?string $currentLogoUrl = null,
        string $name = 'logo',
        ?string $label = null,
        ?string $helpText = null,
        ?string $cropTitle = null,
        ?string $cropDescription = null,
        float $aspectRatio = 1,
        int $outputWidth = 1024,
        int $outputHeight = 1024,
        string $previewClass = 'avatar avatar-lg avatar-square'
    ) {
        $this->currentLogoUrl = $currentLogoUrl;
        $this->name = $name;
        $this->label = $label ?? __('Logo Academy');
        $this->helpText = $helpText ?? __('SVG, PNG, JPG, WEBP maksimal 2MB -- akan diminta crop persegi setelah dipilih');
        $this->cropTitle = $cropTitle ?? __('Sesuaikan Logo');
        $this->cropDescription = $cropDescription ?? __('Geser & perbesar untuk memilih area logo (persegi).');
        $this->aspectRatio = $aspectRatio;
        $this->outputWidth = $outputWidth;
        $this->outputHeight = $outputHeight;
        $this->previewClass = $previewClass;
    }

    public function render(): View
    {
        return view('components.logo-upload-field');
    }
}
