<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Http\Controllers\Admin\Mail\Concerns\RespondsToMailAdminActions;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Services\Admin\Mail\Settings\EmailQuarantineAdminActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailQuarantineRetryController extends Controller
{
    use RespondsToMailAdminActions;

    public function __invoke(
        Request $request,
        EmailMessageQuarantine $quarantine,
        EmailQuarantineAdminActionService $service,
    ): JsonResponse {
        try {
            return $this->accepted(
                $service->retry(
                    quarantine: $quarantine,
                    releasedById: $request
                        ->user()
                        ?->getAuthIdentifier(),
                )
            );
        } catch (MailAdminActionException $exception) {
            return $this->rejected($exception);
        }
    }
}
