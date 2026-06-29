<?php

namespace App\View\Components;
 
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumb extends Component
{
    public function __construct(  public string $title,public array $items=[])
    {
        //
    }

    public function render()
    {
        return view('components.breadcrumb');
    }
}
