<?php

namespace App\Http\Controllers\Admin\System\Queues;

use App\Http\Controllers\Controller;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Services\Admin\System\Queues\QueueActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QueueDriverForceActivationController extends Controller
{
    public function __invoke(
        Request $request,
        QueueDriverConfiguration $configuration,
        QueueActivationService $activation,
    ): RedirectResponse {
        $result = $activation->activate(
            $configuration,
            $request->user(),
            true,
        );

        if (! $result->restartSignaled) {
            return back()->with(
                'error',
                'Queue configuration was force-activated, but workers could not be signaled to restart. A worker restart is still required.',
            );
        }

        return back()->with(
            'success',
            "Queue configuration [{$configuration->name}] force-activated.",
        );
    }
}
