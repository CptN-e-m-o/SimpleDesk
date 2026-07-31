<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Exceptions\Admin\Mail\MailStorageException;
use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\User\User;
use App\Services\Admin\Mail\MailAttachmentDownloadService;
use App\Services\Tickets\TicketAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmailAttachmentDownloadController extends Controller
{
    public function __construct(
        private readonly TicketAccessService $access,
        private readonly MailAttachmentDownloadService $downloads,
    ) {}

    public function __invoke(
        Request $request,
        EmailAttachment $attachment,
    ): StreamedResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $attachment->loadMissing(
            'emailMessage.ticket'
        );

        $ticket = $attachment
            ->emailMessage
            ?->ticket;

        abort_if($ticket === null, 404);

        abort_unless(
            $this->access->canView(
                $user,
                $ticket
            ),
            404
        );

        try {
            return $this->downloads->download(
                $attachment
            );
        } catch (MailStorageException $exception) {
            report($exception);

            abort(404);
        } catch (Throwable $exception) {
            report($exception);

            abort(404);
        }
    }
}
