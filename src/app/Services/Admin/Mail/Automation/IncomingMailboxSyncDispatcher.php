<?php

namespace App\Services\Admin\Mail\Automation;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Jobs\Admin\Mail\SyncIncomingMailboxJob;
use App\Models\Admin\Mail\Mailbox;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Cache;
use Throwable;

class IncomingMailboxSyncDispatcher
{
    /**
     * @param array<int, int|string> $mailboxIds
     */
    public function dispatch(
        array $mailboxIds = [],
        ?int $batchSize = null,
    ): int {
        $batchSize ??= (int) config(
            'simpledesk-mail-automation.sync.batch_size',
            100
        );

        $batchSize = max(
            1,
            min(1000, $batchSize)
        );

        $mailboxIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $id): int => (int) $id,
                        $mailboxIds,
                    ),
                    static fn (int $id): bool => $id > 0,
                )
            )
        );

        $query = Mailbox::query()
            ->where('is_active', true)
            ->whereHas(
                'channels',
                function ($query): void {
                    $query
                        ->where(
                            'direction',
                            MailboxChannelDirection::Incoming->value
                        )
                        ->where('is_enabled', true);
                }
            );

        if ($mailboxIds !== []) {
            $query->whereIn('id', $mailboxIds);
        }

        $ids = $query
            ->orderBy('id')
            ->limit($batchSize)
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $mailboxId) {
            $mailboxId = (int) $mailboxId;

            $lockKey = $this->lockKey(
                $mailboxId
            );

            if (!$this->claim($lockKey)) {
                continue;
            }

            try {
                $pendingDispatch =
                    SyncIncomingMailboxJob::dispatch(
                        $mailboxId
                    );

                $this->configureDispatch(
                    $pendingDispatch
                );

                $dispatched++;
            } catch (Throwable $exception) {
                Cache::forget($lockKey);

                throw $exception;
            }
        }

        return $dispatched;
    }

    private function configureDispatch(
        PendingDispatch $pendingDispatch
    ): void {
        $connection = config(
            'simpledesk-mail-automation.sync.queue_connection'
        );

        if (
            is_string($connection)
            && trim($connection) !== ''
        ) {
            $pendingDispatch->onConnection(
                trim($connection)
            );
        }

        $queue = trim(
            (string) config(
                'simpledesk-mail-automation.sync.queue',
                'mail-incoming'
            )
        );

        if ($queue !== '') {
            $pendingDispatch->onQueue($queue);
        }

        $pendingDispatch->afterCommit();
    }

    private function claim(
        string $key
    ): bool {
        $seconds = max(
            1,
            (int) config(
                'simpledesk-mail-automation.sync.dispatch_lock_seconds',
                55
            )
        );

        return Cache::add(
            $key,
            true,
            $seconds
        );
    }

    private function lockKey(
        int $mailboxId
    ): string {
        return sprintf(
            'simpledesk:mail:scheduled-sync:mailbox:%d',
            $mailboxId
        );
    }
}
