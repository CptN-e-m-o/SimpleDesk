<?php

namespace App\View\Components;

use Illuminate\Http\Request;
use Illuminate\View\Component;

class SortableLink extends Component
{
    public string $href;

    public bool $isActive = false;

    public string $iconDirection = 'asc';

    public function __construct(string $column, public string $title, Request $request)
    {
        $currentSortBy = $request->query('sort_by');
        $currentSortDirection = $request->query('sort_direction', 'asc');

        $this->isActive = ($currentSortBy === $column);

        $newDirection = ($this->isActive && $currentSortDirection === 'asc') ? 'desc' : 'asc';

        if ($this->isActive) {
            $this->iconDirection = $currentSortDirection;
        } else {
            $this->iconDirection = 'desc';
        }

        $queryParams = $request->query();
        $linkParams = array_merge($queryParams, ['sort_by' => $column, 'sort_direction' => $newDirection]);
        unset($linkParams['page']);

        $this->href = route('admin.agents.index', $linkParams);
    }

    public function render()
    {
        return view('components.sortable-link');
    }
}
