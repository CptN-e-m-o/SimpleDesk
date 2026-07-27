<?php

namespace App\Jobs\Admin\Mail;

use App\Exceptions\Admin\Mail\TicketReplyEmailException;
use App\Services\Admin\Mail\Ticketing\TicketReplyEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class QueueTicketReplyEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public int $ticketReplyId,
    ) {
        $this->tries = (int) config(
            'simpledesk-mail-ticketing.outgoing_replies.job.tries',
            5
        );

        $this->timeout = (int) config(
            'simpledesk-mail-ticketing.outgoing_replies.job.timeout',
            120
        );
    }

    public function middleware(): array
    {
        return [
            (
            new WithoutOverlapping(
                'ticket-reply-email:'
                . $this->ticketReplyId
            )
            )
                ->releaseAfter(15)
                ->expireAfter(
                    (int) config(
                        'simpledesk-mail-ticketing.outgoing_replies.job.lock_seconds',
                        300
                    )
                ),
        ];
    }

    public function backoff(): array
    {
        return config(
            'simpledesk-mail-ticketing.outgoing_replies.job.backoff',
            [
                15,
                60,
                180,
                600,
            ]
        );
    }

    public function handle(
        TicketReplyEmailService $service
    ): void {
        try {
            $service->queue(
                ticketReplyId:
                $this->ticketReplyId,
                dispatch: true,
            );
        } catch (
        TicketReplyEmailException $exception
        ) {
            if (!$exception->retryable()) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }
}
