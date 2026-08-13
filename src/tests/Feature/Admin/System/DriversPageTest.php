<?php

namespace Tests\Feature\Admin\System;

use App\Http\Controllers\Admin\System\DriverController;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DriversPageTest extends TestCase
{
    public function test_controller_returns_five_driver_categories(): void
    {
        $response = app(DriverController::class)();

        TestResponse::fromBaseResponse($response->toResponse(request()))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/System/Drivers/Index')
                ->has('categories', 5));
    }
}
