<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\Factory;
use Illuminate\View\View;

class AdminPanelController extends Controller
{
    public function index(): View|Factory
    {
        $sections = collect(config('adminpanel.sections'))
            ->mapWithKeys(fn ($items, $key) => [
                __('lang.'.$key) => array_map(
                    fn ($item) => [
                        'icon' => $item['icon'],
                        'label' => __('lang.'.$item['label']),
                        'route' => $item['route'] ?? null,
                    ],
                    $items
                ),
            ]);

        return view('admin.dashboard', compact('sections'));
    }
}
