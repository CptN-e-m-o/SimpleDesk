<?php

namespace App\Services\Admin\Mail\Antivirus;

use App\Jobs\Admin\Mail\ScanEmailAttachmentJob;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AttachmentScanDispatcher
{
    public function dispatch(
        int $emailAttachmentId
    ): bool {
        if (
            ! (bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            )
        ) {
            return false;
        }

        $lockKey = $this->lockKey(
            $emailAttachmentId
        );

        if (
            ! Cache::add(
                $lockKey,
                true,
                max(
                    1,
                    (int) config(
                        'simpledesk-mail-antivirus.queue.dispatch_lock_seconds',
                        300
                    )
                )
            )
        ) {
            return false;
        }

        try {
            $pendingDispatch =
                ScanEmailAttachmentJob::dispatch(
                    $emailAttachmentId
                );

            $connection = config(
                'simpledesk-mail-antivirus.queue.connection'
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
                    'simpledesk-mail-antivirus.queue.name',
                    'mail-antivirus'
                )
            );

            if ($queue !== '') {
                $pendingDispatch->onQueue(
                    $queue
                );
            }

            $pendingDispatch->afterCommit();

            return true;
        } catch (Throwable $exception) {
            Cache::forget($lockKey);

            throw $exception;
        }
    }

    public function releaseClaim(
        int $emailAttachmentId
    ): void {
        Cache::forget(
            $this->lockKey(
                $emailAttachmentId
            )
        );
    }

    private function lockKey(
        int $emailAttachmentId
    ): string {
        return sprintf(
            'simpledesk:mail:attachment-scan-dispatch:%d',
            $emailAttachmentId
        );
    }
}
