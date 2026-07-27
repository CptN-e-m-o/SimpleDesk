<?php

namespace App\Jobs\Admin\Mail;

use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncIncomingMailboxJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public function __construct(
        public int $mailboxId,
    ) {
        $this->tries = (int) config(
            'simpledesk-mail.jobs.incoming.tries',
            5
        );

        $this->timeout = (int) config(
            'simpledesk-mail.jobs.incoming.timeout',
            300
        );
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                "incoming-mailbox:{$this->mailboxId}"
            ))
                ->releaseAfter(30)
                ->expireAfter(
                    (int) config(
                        'simpledesk-mail.jobs.incoming.lock_seconds',
                        600
                    )
                ),
        ];
    }

    public function backoff(): array
    {
        return config(
            'simpledesk-mail.jobs.incoming.backoff',
            [30, 120, 300, 900]
        );
    }

    public function handle(
        IncomingMailboxSyncService $synchronizer
    ): void {
        $mailbox = Mailbox::query()->find(
            $this->mailboxId
        );

        if ($mailbox === null || !$mailbox->is_active) {
            return;
        }

        $synchronizer->synchronize($mailbox);
    }
}
