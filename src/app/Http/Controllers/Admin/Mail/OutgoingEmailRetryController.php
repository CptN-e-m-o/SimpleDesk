<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Http\Controllers\Admin\Mail\Concerns\RespondsToMailAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\Settings\OutgoingEmailRetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutgoingEmailRetryController extends Controller
{
    use RespondsToMailAdminActions;

    public function __invoke(
        Request $request,
        EmailMessage $message,
        OutgoingEmailRetryService $service,
    ): JsonResponse {
        try {
            return $this->accepted(
                $service->dispatch(
                    emailMessage: $message,
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
