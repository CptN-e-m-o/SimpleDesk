<?php

namespace App\Console\Commands\Admin\Mail;

use App\Services\Admin\Mail\Automation\IncomingMailboxSyncDispatcher;
use Illuminate\Console\Command;
use Throwable;

class DispatchIncomingMailboxSyncsCommand extends Command
{
    protected $signature =
        'simpledesk:mail:dispatch-syncs
        {--mailbox=* : Synchronize only specified mailbox IDs}
        {--limit= : Maximum number of mailboxes}';

    protected $description =
        'Dispatch incoming mailbox synchronization jobs';

    public function handle(
        IncomingMailboxSyncDispatcher $dispatcher
    ): int {
        $mailboxIds = array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $this->option('mailbox')
        );

        $limitOption = $this->option('limit');

        $limit = is_numeric($limitOption)
            ? (int) $limitOption
            : (int) config(
                'simpledesk-mail-automation.sync.batch_size',
                100
            );

        try {
            $count = $dispatcher->dispatch(
                mailboxIds: $mailboxIds,
                batchSize: $limit,
            );
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            report($exception);

            return self::FAILURE;
        }

        if ($count === 0) {
            $this->info(
                'No incoming mailbox synchronization jobs were dispatched.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "Dispatched mailbox synchronization jobs: {$count}"
        );

        return self::SUCCESS;
    }
}
