<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PerPageSelect extends Component
{
    public function __construct(public int $perPage = 10,
        public array $options = [10, 20, 50],
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.per-page-select');
    }
}
