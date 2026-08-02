<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Http\Controllers\Admin\Mail\Concerns\RespondsToMailAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\Settings\ManualMailboxSyncService;
use Illuminate\Http\JsonResponse;

class MailboxManualSyncController extends Controller
{
    use RespondsToMailAdminActions;

    public function __invoke(
        Mailbox $mailbox,
        ManualMailboxSyncService $service,
    ): JsonResponse {
        try {
            return $this->accepted(
                $service->dispatch($mailbox)
            );
        } catch (MailAdminActionException $exception) {
            return $this->rejected($exception);
        }
    }
}
