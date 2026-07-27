<?php

namespace App\Observers\Admin\Mail;

use App\Jobs\Admin\Mail\QueueTicketReplyEmailJob;
use App\Models\TicketReply;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

class TicketReplyObserver implements
    ShouldHandleEventsAfterCommit
{
    public function created(
        TicketReply $reply
    ): void {
        if (
            !(bool) config(
                'simpledesk-mail-ticketing.outgoing_replies.enabled',
                true
            )
        ) {
            return;
        }

        if ($reply->is_internal) {
            return;
        }

        if (
            $reply
                ->incomingEmailMessage()
                ->exists()
        ) {
            return;
        }

        try {
            $pendingDispatch =
                QueueTicketReplyEmailJob::dispatch(
                    $reply->id
                );

            $connection = config(
                'simpledesk-mail-ticketing.outgoing_replies.queue_connection'
            );

            if (
                is_string($connection)
                && $connection !== ''
            ) {
                $pendingDispatch
                    ->onConnection(
                        $connection
                    );
            }

            $pendingDispatch->onQueue(
                (string) config(
                    'simpledesk-mail-ticketing.outgoing_replies.queue',
                    'mail-outgoing'
                )
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
