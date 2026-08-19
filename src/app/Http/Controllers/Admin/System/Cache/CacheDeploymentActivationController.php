<?php

namespace App\Http\Controllers\Admin\System\Cache;

use App\Http\Controllers\Controller;
use App\Services\Admin\System\Cache\CacheActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CacheDeploymentActivationController extends Controller
{
    public function __invoke(Request $request, CacheActivationService $service): RedirectResponse
    {
        $result = $service->activateDeployment($request->user());

        return back()->with($result->restartSignaled ? 'success' : 'error', 'Deployment Cache ownership committed. Queue worker restart signal status: '.($result->restartSignaled ? 'issued' : 'failed').'.');
    }
}
