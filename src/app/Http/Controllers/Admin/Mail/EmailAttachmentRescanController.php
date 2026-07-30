<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Http\Controllers\Admin\Mail\Concerns\RespondsToMailAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\EmailAttachment;
use App\Services\Admin\Mail\Settings\EmailAttachmentRescanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailAttachmentRescanController extends Controller
{
    use RespondsToMailAdminActions;

    public function __invoke(
        Request $request,
        EmailAttachment $attachment,
        EmailAttachmentRescanService $service,
    ): JsonResponse {
        try {
            return $this->accepted(
                $service->dispatch(
                    attachment: $attachment,
                    requestedById: $request
                        ->user()
                        ?->getAuthIdentifier(),
                )
            );
        } catch (MailAdminActionException $exception) {
            return $this->rejected($exception);
        }
    }
}
