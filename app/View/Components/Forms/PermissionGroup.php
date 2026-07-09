<?php

namespace App\View\Components\Forms;

use App\Support\PermissionPresenter;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class PermissionGroup extends Component
{
    public string $module;
    public Collection $items;
    public array $selected;

    public function __construct(string $module, Collection $permissions, array $selected = [])
    {
        $this->module = $module;
        $this->selected = $selected;

        $this->items = $permissions->map(
            fn ($permission) => PermissionPresenter::present($permission)
        );
    }
 

    public function render(): View|Closure|string
    {
        return view('components.forms.permission-group');
    }
}