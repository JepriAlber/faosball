<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CurrencyInput extends Component
{
    public string $name;

    public string $id;

    public string $rawValue;

    public function __construct(string $name, mixed $value = null, ?string $id = null)
    {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->rawValue = $value === null || $value === '' ? '' : (string) $value;
    }

    public function render(): View
    {
        return view('components.currency-input');
    }
}
