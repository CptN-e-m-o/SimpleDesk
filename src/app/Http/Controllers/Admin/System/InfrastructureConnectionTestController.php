<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\Admin\System\InfrastructureHealthTrigger;
use App\Http\Controllers\Controller;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfrastructureConnectionTestController extends Controller
{
    public function __construct(private readonly InfrastructureConnectionHealthService $health) {}

    public function __invoke(Request $r, InfrastructureConnection $connection): JsonResponse
    {
        return response()->json($this->health->test($connection, InfrastructureHealthTrigger::Manual, $r->user())->toArray());
    }
}
