<?php

namespace App\Console\Commands\Admin\Mail;

use App\Jobs\Admin\Mail\SyncIncomingMailboxJob;
use App\Models\Admin\Mail\Mailbox;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncIncomingMailboxCommand extends Command
{
    protected $signature = 'simpledesk:mail:sync
        {mailbox : Mailbox ID}
        {--queue : Dispatch synchronization to the queue}';

    protected $description =
        'Synchronize incoming email for a mailbox';

    public function handle(
        IncomingMailboxSyncService $synchronizer
    ): int {
        $mailbox = Mailbox::query()->find(
            $this->argument('mailbox')
        );

        if ($mailbox === null) {
            $this->error(
                'Mailbox was not found.'
            );

            return self::FAILURE;
        }

        if (! $mailbox->is_active) {
            $this->error(
                'Mailbox is disabled.'
            );

            return self::FAILURE;
        }

        if ((bool) $this->option('queue')) {
            SyncIncomingMailboxJob::dispatch(
                $mailbox->id
            )->onQueue(
                config(
                    'simpledesk-mail.queues.incoming',
                    'mail-incoming'
                )
            );

            $this->info(
                "Mailbox [{$mailbox->id}] "
                .'synchronization was queued.'
            );

            return self::SUCCESS;
        }

        try {
            $result = $synchronizer->synchronize(
                $mailbox
            );

            $this->info(
                'Incoming mailbox synchronization completed.'
            );

            $this->table(
                [
                    'Parameter',
                    'Value',
                ],
                [
                    [
                        'Mailbox',
                        $result->mailboxId,
                    ],
                    [
                        'Channel',
                        $result->mailboxChannelId,
                    ],
                    [
                        'Driver',
                        $result->driver->value,
                    ],
                    [
                        'Pages',
                        $result->pages,
                    ],
                    [
                        'Fetched',
                        $result->fetched,
                    ],
                    [
                        'Stored',
                        $result->stored,
                    ],
                    [
                        'Duplicates',
                        $result->duplicates,
                    ],
                    [
                        'Acknowledged',
                        $result->acknowledged,
                    ],
                    [
                        'Truncated',
                        $result->truncated
                            ? 'yes'
                            : 'no',
                    ],
                    [
                        'Next cursor',
                        $result->nextCursor
                        ?? 'null',
                    ],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
