<?php

namespace App\View\Components;

use App\Models\Staff;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class SalaryAmount extends Component
{
    public string $display;

    public function __construct(Staff $staff, ?float $amount)
    {
        $this->display = match (true) {
            $amount === null => '-',
            ! (Auth::user()?->can('viewSalary', $staff) ?? false) => '*****',
            default => 'Rp ' . number_format($amount, 0, ',', '.'),
        };
    }

    public function render(): View
    {
        return view('components.salary-amount');
    }
}
