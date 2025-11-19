<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CreateButton extends Component
{
    public string $routeName;

    public string $title;

    public function __construct(string $routeName, string $title)
    {
        $this->routeName = $routeName;
        $this->title = $title;
    }

    public function render(): View|Closure|string
    {
        return view('components.create-button');
    }
}
