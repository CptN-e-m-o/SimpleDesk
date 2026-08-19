<?php

namespace App\Http\Controllers\Admin\System\Cache;

use App\Http\Controllers\Controller;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CacheDriverActivationController extends Controller
{
    public function __invoke(Request $request, CacheDriverConfiguration $configuration, CacheActivationService $service): RedirectResponse
    {
        $result = $service->activate($configuration, $request->user());

        return back()->with($result->restartSignaled ? 'success' : 'error', $result->restartSignaled ? 'Cache configuration activated; queue workers were signaled to restart.' : 'Cache configuration activated, but queue workers could not be signaled. Restart them operationally.');
    }
}
