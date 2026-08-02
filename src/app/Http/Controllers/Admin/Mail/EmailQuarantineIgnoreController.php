<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Http\Controllers\Admin\Mail\Concerns\RespondsToMailAdminActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Mail\Quarantine\IgnoreEmailQuarantineRequest;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Services\Admin\Mail\Settings\EmailQuarantineAdminActionService;
use Illuminate\Http\JsonResponse;

class EmailQuarantineIgnoreController extends Controller
{
    use RespondsToMailAdminActions;

    public function __invoke(
        IgnoreEmailQuarantineRequest $request,
        EmailMessageQuarantine $quarantine,
        EmailQuarantineAdminActionService $service,
    ): JsonResponse {
        try {
            return $this->accepted(
                $service->ignore(
                    quarantine: $quarantine,
                    releasedById: $request
                        ->user()
                        ?->getAuthIdentifier(),
                    reason: $request->validated('reason'),
                )
            );
        } catch (MailAdminActionException $exception) {
            return $this->rejected($exception);
        }
    }
}
