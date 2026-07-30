<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsService;
use Illuminate\Http\JsonResponse;

class MailboxDiagnosticsController extends Controller
{
    public function __invoke(
        Mailbox $mailbox,
        MailDiagnosticsService $diagnostics,
    ): JsonResponse {
        return response()->json([
            'data' =>
                $diagnostics->mailbox(
                    $mailbox
                ),
        ]);
    }
}
