<?php

namespace App\Jobs\Admin\Mail;

use App\Enums\Admin\Mail\EmailQuarantineStage;
use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use App\Services\Admin\Mail\Ticketing\InboundEmailTicketProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ProcessInboundEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public int $emailMessageId,
    ) {
        $this->tries = (int) config(
            'simpledesk-mail-ticketing.job.tries',
            5
        );

        $this->timeout = (int) config(
            'simpledesk-mail-ticketing.job.timeout',
            120
        );
    }

    public function middleware(): array
    {
        return [
            (
            new WithoutOverlapping(
                'inbound-email-ticketing:'
                .$this->emailMessageId
            )
            )
                ->releaseAfter(15)
                ->expireAfter(
                    (int) config(
                        'simpledesk-mail-ticketing.job.lock_seconds',
                        300
                    )
                ),
        ];
    }

    public function backoff(): array
    {
        return config(
            'simpledesk-mail-ticketing.job.backoff',
            [
                15,
                60,
                180,
                600,
            ]
        );
    }

    public function handle(
        InboundEmailTicketProcessor $processor,
        EmailMessageQuarantineService $quarantine,
    ): void {
        try {
            $processor->process(
                $this->emailMessageId
            );

            $quarantine->resolveForEmail(
                $this->emailMessageId
            );
        } catch (
            InboundEmailTicketingException $exception
        ) {
            if (! $exception->retryable()) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    public function failed(
        ?Throwable $exception
    ): void {
        if (
            ! (bool) config(
                'simpledesk-mail-quarantine.enabled',
                true
            )
        ) {
            return;
        }

        try {
            app(
                EmailMessageQuarantineService::class
            )->quarantine(
                emailMessageId: $this->emailMessageId,

                stage: EmailQuarantineStage::InboundTicketing,

                exception: $exception,
            );
        } catch (Throwable $quarantineException) {
            report(
                $quarantineException
            );
        }
    }
}
