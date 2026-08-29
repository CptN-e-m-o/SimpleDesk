<?php

namespace App\Http\Controllers\Admin\System\Cache;

use App\Http\Controllers\Controller;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CacheDriverForceActivationController extends Controller
{
    public function __invoke(Request $request, CacheDriverConfiguration $configuration, CacheActivationService $service): RedirectResponse
    {
        $result = $service->activate($configuration, $request->user(), true);

        return back()->with($result->restartSignaled ? 'success' : 'error', 'Force activation committed. Queue worker restart signal status: '.($result->restartSignaled ? 'issued' : 'failed').'.');
    }
}
