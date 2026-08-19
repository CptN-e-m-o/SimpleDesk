<?php
namespace App\Http\Controllers\Admin\System\Cache;
use App\Http\Controllers\Controller; use App\Models\Admin\System\CacheDriverConfiguration; use App\Services\Admin\System\Cache\CacheDriverHealthService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class CacheDriverConfigurationTestController extends Controller { public function __invoke(Request $request, CacheDriverConfiguration $configuration, CacheDriverHealthService $health): JsonResponse { return response()->json($health->test($configuration, $request->user())->toArray()); } }
