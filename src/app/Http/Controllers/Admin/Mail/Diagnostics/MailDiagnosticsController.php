<?php

namespace App\Http\Controllers\Admin\Mail\Diagnostics;

use App\Http\Controllers\Controller;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsService;
use Inertia\Inertia;
use Inertia\Response;

class MailDiagnosticsController extends Controller
{
    public function index(MailDiagnosticsService $diagnostics): Response
    {
        return Inertia::render(
            'Admin/Email/Diagnostics/Index',
            $diagnostics->dashboard(),
        );
    }
}
