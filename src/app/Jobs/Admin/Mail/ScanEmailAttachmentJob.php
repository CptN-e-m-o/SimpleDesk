<?php

namespace App\Jobs\Admin\Mail;

use App\Exceptions\Admin\Mail\AttachmentScanException;
use App\Services\Admin\Mail\Antivirus\AttachmentScanDispatcher;
use App\Services\Admin\Mail\Antivirus\EmailAttachmentScanService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ScanEmailAttachmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public int $emailAttachmentId,
    ) {
        $this->tries = max(
            1,
            (int) config(
                'simpledesk-mail-antivirus.queue.tries',
                5
            )
        );

        $this->timeout = max(
            1,
            (int) config(
                'simpledesk-mail-antivirus.queue.timeout',
                120
            )
        );
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                "email-attachment-scan:{$this->emailAttachmentId}"
            ))
                ->releaseAfter(30)
                ->expireAfter(
                    max(
                        60,
                        (int) config(
                            'simpledesk-mail-antivirus.queue.lock_seconds',
                            300
                        )
                    )
                ),
        ];
    }

    public function backoff(): array
    {
        return (array) config(
            'simpledesk-mail-antivirus.queue.backoff',
            [
                30,
                120,
                300,
                900,
            ]
        );
    }

    public function handle(
        EmailAttachmentScanService $scanner,
        AttachmentScanDispatcher $dispatcher,
    ): void {
        try {
            $scanner->scan(
                $this->emailAttachmentId
            );

            $dispatcher->releaseClaim(
                $this->emailAttachmentId
            );
        } catch (AttachmentScanException $exception) {
            if (! $exception->retryable()) {
                $scanner->markFailed(
                    emailAttachmentId: $this->emailAttachmentId,
                    exception: $exception,
                );

                $dispatcher->releaseClaim(
                    $this->emailAttachmentId
                );

                return;
            }

            $scanner->recordRetryableFailure(
                emailAttachmentId: $this->emailAttachmentId,
                exception: $exception,
            );

            throw $exception;
        } catch (Throwable $exception) {
            $scanException = new AttachmentScanException(
                message: $exception->getMessage(),
                errorCode: 'unexpected_attachment_scan_error',
                retryable: true,
                previous: $exception,
            );

            $scanner->recordRetryableFailure(
                emailAttachmentId: $this->emailAttachmentId,
                exception: $scanException,
            );

            throw $scanException;
        }
    }

    public function failed(
        ?Throwable $exception
    ): void {
        $scanner = app(
            EmailAttachmentScanService::class
        );

        $scanner->markFailed(
            emailAttachmentId: $this->emailAttachmentId,
            exception: $exception instanceof AttachmentScanException
                ? $exception
                : new AttachmentScanException(
                    message: $exception?->getMessage()
                    ?? 'Attachment antivirus scan job failed.',
                    errorCode: 'attachment_scan_job_failed',
                    retryable: false,
                    previous: $exception,
                ),
        );

        app(
            AttachmentScanDispatcher::class
        )->releaseClaim(
            $this->emailAttachmentId
        );
    }
}
