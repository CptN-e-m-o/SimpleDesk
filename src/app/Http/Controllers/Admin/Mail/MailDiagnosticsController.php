<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsService;
use Illuminate\Http\JsonResponse;

class MailDiagnosticsController extends Controller
{
    public function __invoke(
        MailDiagnosticsService $diagnostics
    ): JsonResponse {
        return response()->json([
            'data' => $diagnostics->overview(),
        ]);
    }
}
