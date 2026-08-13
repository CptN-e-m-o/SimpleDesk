<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\Admin\System\DriverCategory;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/System/Drivers/Index', ['categories' => array_map(fn ($c) => $c->value, DriverCategory::cases())]);
    }
}
