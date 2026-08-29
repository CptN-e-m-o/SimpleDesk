<?php

namespace App\Http\Controllers\Admin\System\Queues;

use App\Http\Controllers\Controller;
use App\Services\Admin\System\Queues\QueueActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QueueDeploymentForceActivationController extends Controller
{
    public function __invoke(
        Request $request,
        QueueActivationService $activation,
    ): RedirectResponse {
        $result = $activation->activateDeployment(
            $request->user(),
            true,
        );

        if (! $result->restartSignaled) {
            return back()->with(
                'error',
                'Deployment Queue configuration was force-activated, but workers could not be signaled to restart. A worker restart is still required.',
            );
        }

        return back()->with(
            'success',
            'Deployment Queue configuration force-activated.',
        );
    }
}
