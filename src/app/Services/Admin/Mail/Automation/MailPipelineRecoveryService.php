<?php

namespace App\Services\Admin\Mail\Automation;

use App\Data\Admin\Mail\MailRecoveryResultData;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use BackedEnum;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class MailPipelineRecoveryService
{
    public function recover(
        ?int $limit = null
    ): MailRecoveryResultData {
        $limit ??= (int) config(
            'simpledesk-mail-automation.recovery.batch_size',
            100
        );

        $limit = max(
            1,
            min(1000, $limit)
        );

        $incomingStuckReset =
            $this->recoverStuckIncomingMessages(
                $limit
            );

        $incomingReceivedDispatched =
            $this->dispatchReceivedIncomingMessages(
                $limit
            );

        $outgoingStuckReset =
            $this->recoverStuckOutgoingMessages(
                $limit
            );

        $outgoingQueuedDispatched =
            $this->dispatchQueuedOutgoingMessages(
                $limit
            );

        return new MailRecoveryResultData(
            incomingStuckReset:
            $incomingStuckReset,

            incomingReceivedDispatched:
            $incomingReceivedDispatched,

            outgoingStuckReset:
            $outgoingStuckReset,

            outgoingQueuedDispatched:
            $outgoingQueuedDispatched,
        );
    }

    private function recoverStuckIncomingMessages(
        int $limit
    ): int {
        $timeoutSeconds = max(
            60,
            (int) config(
                'simpledesk-mail-automation.recovery.incoming_processing_timeout_seconds',
                900
            )
        );

        $cutoff = now()->subSeconds(
            $timeoutSeconds
        );

        $ids = EmailMessage::query()
            ->where('direction', 'incoming')
            ->where('status', 'processing')
            ->whereNotNull('processing_started_at')
            ->where(
                'processing_started_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $reset = 0;

        foreach ($ids as $emailMessageId) {
            $emailMessageId =
                (int) $emailMessageId;

            $wasReset = DB::transaction(
                function () use (
                    $emailMessageId,
                    $cutoff,
                ): bool {
                    $emailMessage =
                        EmailMessage::query()
                            ->lockForUpdate()
                            ->find(
                                $emailMessageId
                            );

                    if ($emailMessage === null) {
                        return false;
                    }

                    if (
                        $this->statusValue(
                            $emailMessage
                        ) !== 'processing'
                    ) {
                        return false;
                    }

                    if (
                        $emailMessage
                            ->processing_started_at
                        === null
                        || $emailMessage
                            ->processing_started_at
                            ->greaterThan($cutoff)
                    ) {
                        return false;
                    }

                    $emailMessage->forceFill([
                        'status' => 'received',

                        'processing_started_at' =>
                            null,

                        'failed_at' => null,
                        'failure_code' => null,
                        'failure_message' => null,

                        'metadata' =>
                            $this->appendRecoveryMetadata(
                                $emailMessage,
                                'incoming_processing_reset'
                            ),
                    ])->save();

                    return true;
                },
                3,
            );

            if (!$wasReset) {
                continue;
            }

            $reset++;

            $this->dispatchInboundProcessing(
                $emailMessageId
            );
        }

        return $reset;
    }

    private function dispatchReceivedIncomingMessages(
        int $limit
    ): int {
        $graceSeconds = max(
            0,
            (int) config(
                'simpledesk-mail-automation.recovery.grace_seconds',
                120
            )
        );

        $cutoff = now()->subSeconds(
            $graceSeconds
        );

        $ids = EmailMessage::query()
            ->where('direction', 'incoming')
            ->where('status', 'received')
            ->where(
                'created_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $emailMessageId) {
            if (
                $this->dispatchInboundProcessing(
                    (int) $emailMessageId
                )
            ) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    private function recoverStuckOutgoingMessages(
        int $limit
    ): int {
        $timeoutSeconds = max(
            60,
            (int) config(
                'simpledesk-mail-automation.recovery.outgoing_sending_timeout_seconds',
                900
            )
        );

        $cutoff = now()->subSeconds(
            $timeoutSeconds
        );

        $ids = EmailMessage::query()
            ->where('direction', 'outgoing')
            ->where('status', 'sending')
            ->whereNotNull('processing_started_at')
            ->where(
                'processing_started_at',
                '<=',
                $cutoff
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $reset = 0;

        foreach ($ids as $emailMessageId) {
            $emailMessageId =
                (int) $emailMessageId;

            $wasReset = DB::transaction(
                function () use (
                    $emailMessageId,
                    $cutoff,
                ): bool {
                    $emailMessage =
                        EmailMessage::query()
                            ->lockForUpdate()
                            ->find(
                                $emailMessageId
                            );

                    if ($emailMessage === null) {
                        return false;
                    }

                    if (
                        $this->statusValue(
                            $emailMessage
                        ) !== 'sending'
                    ) {
                        return false;
                    }

                    if (
                        $emailMessage
                            ->processing_started_at
                        === null
                        || $emailMessage
                            ->processing_started_at
                            ->greaterThan($cutoff)
                    ) {
                        return false;
                    }

                    $emailMessage->forceFill([
                        'status' => 'queued',

                        'processing_started_at' =>
                            null,

                        'failed_at' => null,
                        'failure_code' => null,
                        'failure_message' => null,

                        'metadata' =>
                            $this->appendRecoveryMetadata(
                                $emailMessage,
                                'outgoing_sending_reset'
                            ),
                    ])->save();

                    return true;
                },
                3,
            );

            if (!$wasReset) {
                continue;
            }

            $reset++;

            $this->dispatchOutgoingSending(
                $emailMessageId
            );
        }

        return $reset;
    }

    private function dispatchQueuedOutgoingMessages(
        int $limit
    ): int {
        $graceSeconds = max(
            0,
            (int) config(
                'simpledesk-mail-automation.recovery.grace_seconds',
                120
            )
        );

        $cutoff = now()->subSeconds(
            $graceSeconds
        );

        $ids = EmailMessage::query()
            ->where('direction', 'outgoing')
            ->where('status', 'queued')
            ->where(
                function ($query) use (
                    $cutoff
                ): void {
                    $query
                        ->where(
                            'queued_at',
                            '<=',
                            $cutoff
                        )
                        ->orWhere(
                            function ($query) use (
                                $cutoff
                            ): void {
                                $query
                                    ->whereNull(
                                        'queued_at'
                                    )
                                    ->where(
                                        'created_at',
                                        '<=',
                                        $cutoff
                                    );
                            }
                        );
                }
            )
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $dispatched = 0;

        foreach ($ids as $emailMessageId) {
            if (
                $this->dispatchOutgoingSending(
                    (int) $emailMessageId
                )
            ) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    private function dispatchInboundProcessing(
        int $emailMessageId
    ): bool {
        $lockKey = sprintf(
            'simpledesk:mail:recovery:inbound:%d',
            $emailMessageId
        );

        if (!$this->claim($lockKey)) {
            return false;
        }

        try {
            $pendingDispatch =
                ProcessInboundEmailJob::dispatch(
                    $emailMessageId
                );

            $this->configureDispatch(
                pendingDispatch:
                $pendingDispatch,

                queue: (string) config(
                    'simpledesk-mail-automation.recovery.incoming_queue',
                    'mail-incoming'
                ),
            );

            return true;
        } catch (Throwable $exception) {
            Cache::forget($lockKey);

            throw $exception;
        }
    }

    private function dispatchOutgoingSending(
        int $emailMessageId
    ): bool {
        $lockKey = sprintf(
            'simpledesk:mail:recovery:outgoing:%d',
            $emailMessageId
        );

        if (!$this->claim($lockKey)) {
            return false;
        }

        try {
            $pendingDispatch =
                SendOutgoingEmailJob::dispatch(
                    $emailMessageId
                );

            $this->configureDispatch(
                pendingDispatch:
                $pendingDispatch,

                queue: (string) config(
                    'simpledesk-mail-automation.recovery.outgoing_queue',
                    'mail-outgoing'
                ),
            );

            return true;
        } catch (Throwable $exception) {
            Cache::forget($lockKey);

            throw $exception;
        }
    }

    private function configureDispatch(
        PendingDispatch $pendingDispatch,
        string $queue,
    ): void {
        $connection = config(
            'simpledesk-mail-automation.recovery.queue_connection'
        );

        if (
            is_string($connection)
            && trim($connection) !== ''
        ) {
            $pendingDispatch->onConnection(
                trim($connection)
            );
        }

        $queue = trim($queue);

        if ($queue !== '') {
            $pendingDispatch->onQueue(
                $queue
            );
        }

        $pendingDispatch->afterCommit();
    }

    private function claim(
        string $key
    ): bool {
        $seconds = max(
            1,
            (int) config(
                'simpledesk-mail-automation.recovery.dispatch_lock_seconds',
                300
            )
        );

        return Cache::add(
            $key,
            true,
            $seconds
        );
    }

    private function statusValue(
        EmailMessage $emailMessage
    ): string {
        $status = $emailMessage->status;

        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return (string) $status;
    }

    private function appendRecoveryMetadata(
        EmailMessage $emailMessage,
        string $action,
    ): array {
        $metadata = is_array(
            $emailMessage->metadata
        )
            ? $emailMessage->metadata
            : [];

        $recovery = isset(
            $metadata['recovery']
        ) && is_array(
            $metadata['recovery']
        )
            ? $metadata['recovery']
            : [];

        $events = isset(
            $recovery['events']
        ) && is_array(
            $recovery['events']
        )
            ? $recovery['events']
            : [];

        $events[] = [
            'action' => $action,
            'recovered_at' =>
                now()->toIso8601String(),
        ];

        $recovery['events'] = array_slice(
            $events,
            -20
        );

        $recovery['last_action'] =
            $action;

        $recovery['last_recovered_at'] =
            now()->toIso8601String();

        $metadata['recovery'] =
            $recovery;

        return $metadata;
    }
}
