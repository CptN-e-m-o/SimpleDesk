<?php

namespace App\Http\Controllers\Admin\System\Queues;

use App\Http\Controllers\Controller;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Services\Admin\System\Queues\QueueDriverHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueDriverConfigurationTestController extends Controller
{
    public function __construct(private readonly QueueDriverHealthService $health) {}

    public function __invoke(Request $r, QueueDriverConfiguration $configuration): JsonResponse
    {
        return response()->json($this->health->test($configuration, $r->user())->toArray());
    }
}
