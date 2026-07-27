<?php

namespace App\Jobs\Admin\Mail;

use App\Enums\Mail\EmailMessageStatus;
use App\Models\Admin\Mail\EmailMessage;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SendOutgoingEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public int $emailMessageId,
    ) {
        $this->tries = (int) config(
            'simpledesk-mail.jobs.outgoing.tries',
            5
        );

        $this->timeout = (int) config(
            'simpledesk-mail.jobs.outgoing.timeout',
            300
        );
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                "outgoing-email:{$this->emailMessageId}"
            ))
                ->releaseAfter(30)
                ->expireAfter(
                    (int) config(
                        'simpledesk-mail.jobs.outgoing.lock_seconds',
                        600
                    )
                ),
        ];
    }

    public function backoff(): array
    {
        return config(
            'simpledesk-mail.jobs.outgoing.backoff',
            [30, 120, 300, 900]
        );
    }

    public function handle(
        OutgoingMailFailoverService $sender
    ): void {
        $emailMessage = EmailMessage::query()->find(
            $this->emailMessageId
        );

        if ($emailMessage === null) {
            return;
        }

        if (in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Sent,
                EmailMessageStatus::Delivered,
            ],
            true,
        )) {
            return;
        }

        $sender->send($emailMessage);
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $emailMessage = EmailMessage::query()->find(
            $this->emailMessageId
        );

        if ($emailMessage === null) {
            return;
        }

        if (in_array(
            $emailMessage->status,
            [
                EmailMessageStatus::Sent,
                EmailMessageStatus::Delivered,
            ],
            true,
        )) {
            return;
        }

        $emailMessage->forceFill([
            'status' => EmailMessageStatus::Failed,
            'failed_at' => now(),
            'failure_code' => 'queue_job_failed',
            'failure_message' =>
                $exception?->getMessage()
                ?? 'Outgoing email queue job failed.',
        ])->save();
    }
}
