<?php

namespace App\Services\Admin\Mail\Quarantine;

use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\EmailQuarantineResolution;
use App\Enums\Admin\Mail\EmailQuarantineStage;
use App\Exceptions\Admin\Mail\EmailQuarantineException;
use App\Exceptions\Admin\Mail\InboundEmailTicketingException;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmailMessageQuarantineService
{
    public function quarantine(
        int $emailMessageId,
        EmailQuarantineStage $stage,
        ?Throwable $exception = null,
        ?string $reasonCode = null,
        ?string $reasonMessage = null,
        array $metadata = [],
    ): EmailMessageQuarantine {
        return DB::transaction(
            function () use (
                $emailMessageId,
                $stage,
                $exception,
                $reasonCode,
                $reasonMessage,
                $metadata,
            ): EmailMessageQuarantine {
                $emailMessage = EmailMessage::query()
                    ->lockForUpdate()
                    ->findOrFail($emailMessageId);

                $reasonCode ??=
                    $this->reasonCode(
                        emailMessage: $emailMessage,

                        exception: $exception,
                    );

                $reasonMessage ??=
                    $this->reasonMessage(
                        emailMessage: $emailMessage,

                        exception: $exception,
                    );

                $quarantine =
                    EmailMessageQuarantine::query()
                        ->lockForUpdate()
                        ->firstOrNew([
                            'email_message_id' => $emailMessage->id,
                        ]);

                $now = now();

                $attempts =
                    max(
                        0,
                        (int) $quarantine->attempts
                    ) + 1;

                $event = [
                    'action' => 'quarantined',

                    'stage' => $stage->value,

                    'reason_code' => $reasonCode,

                    'reason_message' => $reasonMessage,

                    'exception_class' => $exception !== null
                            ? $exception::class
                            : null,

                    'attempt' => $attempts,

                    'created_at' => $now->toIso8601String(),
                ];

                $quarantine->forceFill([
                    'mailbox_id' => $emailMessage->mailbox_id,

                    'mailbox_channel_id' => $emailMessage
                        ->mailbox_channel_id,

                    'stage' => $stage,

                    'reason_code' => $this->limitString(
                        $reasonCode,
                        255
                    ),

                    'reason_message' => $this->limitString(
                        $reasonMessage,
                        10000
                    ),

                    'exception_class' => $exception !== null
                            ? $this->limitString(
                                $exception::class,
                                255
                            )
                            : null,

                    'attempts' => $attempts,

                    'first_quarantined_at' => $quarantine->exists
                            ? $quarantine
                                ->first_quarantined_at
                            : $now,

                    'last_quarantined_at' => $now,

                    'released_at' => null,
                    'released_by_id' => null,

                    'resolved_at' => null,
                    'resolution' => null,

                    'metadata' => $this->appendMetadataEvent(
                        current: $quarantine->metadata,

                        event: array_merge(
                            $event,
                            $metadata,
                        ),
                    ),
                ])->save();

                $this->markEmailMessageFailed(
                    emailMessage: $emailMessage,

                    reasonMessage: $reasonMessage,
                );

                return $quarantine->fresh([
                    'emailMessage',
                    'mailbox',
                    'mailboxChannel',
                ]);
            },
            3,
        );
    }

    public function retry(
        int $quarantineId,
        ?int $releasedById = null,
        bool $dispatch = true,
    ): EmailMessageQuarantine {
        $quarantine = DB::transaction(
            function () use (
                $quarantineId,
                $releasedById,
            ): EmailMessageQuarantine {
                $quarantine =
                    EmailMessageQuarantine::query()
                        ->with('emailMessage')
                        ->lockForUpdate()
                        ->findOrFail(
                            $quarantineId
                        );

                if ($quarantine->isResolved()) {
                    throw new EmailQuarantineException(
                        message: 'Quarantine record '
                        ."[{$quarantine->id}] "
                        .'has already been resolved.',

                        errorCode: 'quarantine_already_resolved',
                    );
                }

                if (
                    $quarantine->stage
                    !== EmailQuarantineStage::InboundTicketing
                ) {
                    throw new EmailQuarantineException(
                        message: 'Quarantine stage '
                        ."[{$quarantine->stage->value}] "
                        .'does not support this retry method yet.',

                        errorCode: 'unsupported_quarantine_retry_stage',
                    );
                }

                $emailMessage =
                    EmailMessage::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $quarantine
                                ->email_message_id
                        );

                if (
                    $emailMessage->direction
                    !== EmailMessageDirection::Incoming
                ) {
                    throw new EmailQuarantineException(
                        message: 'Email message '
                        ."[{$emailMessage->id}] "
                        .'is not incoming.',

                        errorCode: 'quarantine_email_not_incoming',
                    );
                }

                $now = now();

                $emailMetadata =
                    is_array(
                        $emailMessage->metadata
                    )
                        ? $emailMessage->metadata
                        : [];

                $emailMetadata['quarantine'] = [
                    'action' => 'retry_requested',

                    'quarantine_id' => $quarantine->id,

                    'requested_at' => $now->toIso8601String(),
                ];

                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Received,

                    'processing_started_at' => null,
                    'processed_at' => null,

                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,

                    'metadata' => $emailMetadata,
                ])->save();

                $quarantine->forceFill([
                    'released_at' => $now,

                    'released_by_id' => $releasedById,

                    /*
                     * Пока повторная обработка не закончилась,
                     * resolved_at остаётся null.
                     */
                    'resolved_at' => null,

                    'resolution' => EmailQuarantineResolution::Retried,

                    'metadata' => $this->appendMetadataEvent(
                        current: $quarantine->metadata,

                        event: [
                            'action' => 'retry_requested',

                            'released_by_id' => $releasedById,

                            'created_at' => $now
                                ->toIso8601String(),
                        ],
                    ),
                ])->save();

                return $quarantine->fresh([
                    'emailMessage',
                ]);
            },
            3,
        );

        if ($dispatch) {
            $this->dispatchRetry(
                $quarantine->email_message_id
            );
        }

        return $quarantine;
    }

    public function ignore(
        int $quarantineId,
        ?int $releasedById = null,
        ?string $reason = null,
    ): EmailMessageQuarantine {
        return DB::transaction(
            function () use (
                $quarantineId,
                $releasedById,
                $reason,
            ): EmailMessageQuarantine {
                $quarantine =
                    EmailMessageQuarantine::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $quarantineId
                        );

                if ($quarantine->isResolved()) {
                    throw new EmailQuarantineException(
                        message: 'Quarantine record '
                        ."[{$quarantine->id}] "
                        .'has already been resolved.',

                        errorCode: 'quarantine_already_resolved',
                    );
                }

                $emailMessage =
                    EmailMessage::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $quarantine
                                ->email_message_id
                        );

                $now = now();

                $emailMetadata =
                    is_array(
                        $emailMessage->metadata
                    )
                        ? $emailMessage->metadata
                        : [];

                $emailMetadata['ticketing'] = [
                    'action' => 'manually_ignored',

                    'quarantine_id' => $quarantine->id,

                    'reason' => $reason,

                    'ignored_at' => $now->toIso8601String(),
                ];

                $emailMessage->forceFill([
                    'status' => EmailMessageStatus::Processed,

                    'processing_started_at' => null,
                    'processed_at' => $now,

                    'failed_at' => null,
                    'failure_code' => null,
                    'failure_message' => null,

                    'metadata' => $emailMetadata,
                ])->save();

                $quarantine->forceFill([
                    'released_at' => $now,

                    'released_by_id' => $releasedById,

                    'resolved_at' => $now,

                    'resolution' => EmailQuarantineResolution::Ignored,

                    'metadata' => $this->appendMetadataEvent(
                        current: $quarantine->metadata,

                        event: [
                            'action' => 'manually_ignored',

                            'reason' => $reason,

                            'released_by_id' => $releasedById,

                            'created_at' => $now
                                ->toIso8601String(),
                        ],
                    ),
                ])->save();

                return $quarantine->fresh([
                    'emailMessage',
                ]);
            },
            3,
        );
    }

    public function resolveForEmail(
        int $emailMessageId
    ): ?EmailMessageQuarantine {
        return DB::transaction(
            function () use (
                $emailMessageId
            ): ?EmailMessageQuarantine {
                $quarantine =
                    EmailMessageQuarantine::query()
                        ->lockForUpdate()
                        ->where(
                            'email_message_id',
                            $emailMessageId
                        )
                        ->whereNull('resolved_at')
                        ->first();

                if ($quarantine === null) {
                    return null;
                }

                $now = now();

                $quarantine->forceFill([
                    'resolved_at' => $now,

                    'resolution' => EmailQuarantineResolution::Resolved,

                    'metadata' => $this->appendMetadataEvent(
                        current: $quarantine->metadata,

                        event: [
                            'action' => 'processing_succeeded',

                            'created_at' => $now
                                ->toIso8601String(),
                        ],
                    ),
                ])->save();

                return $quarantine;
            },
            3,
        );
    }

    private function markEmailMessageFailed(
        EmailMessage $emailMessage,
        string $reasonMessage,
    ): void {
        if (
            $emailMessage->status
            === EmailMessageStatus::Processed
        ) {
            return;
        }

        $emailMessage->forceFill([
            'status' => EmailMessageStatus::Failed,

            'processing_started_at' => null,

            'failed_at' => now(),

            'failure_code' => 'inbound_ticket_processing_quarantined',

            'failure_message' => $this->limitString(
                $reasonMessage,
                10000
            ),
        ])->save();
    }

    private function dispatchRetry(
        int $emailMessageId
    ): void {
        $pendingDispatch =
            ProcessInboundEmailJob::dispatch(
                $emailMessageId
            );

        $this->configureDispatch(
            $pendingDispatch
        );
    }

    private function configureDispatch(
        PendingDispatch $pendingDispatch
    ): void {
        $connection = config(
            'simpledesk-mail-quarantine.queue_connection'
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
                'simpledesk-mail-quarantine.queue',
                'mail-incoming'
            )
        );

        if ($queue !== '') {
            $pendingDispatch->onQueue(
                $queue
            );
        }

        $pendingDispatch->afterCommit();
    }

    private function reasonCode(
        EmailMessage $emailMessage,
        ?Throwable $exception,
    ): string {
        if (
            $exception
            instanceof InboundEmailTicketingException
        ) {
            return $exception->errorCode();
        }

        $failureCode = trim(
            (string) $emailMessage
                ->failure_code
        );

        if ($failureCode !== '') {
            return $failureCode;
        }

        if ($exception !== null) {
            return class_basename(
                $exception
            );
        }

        return 'inbound_ticket_processing_failed';
    }

    private function reasonMessage(
        EmailMessage $emailMessage,
        ?Throwable $exception,
    ): string {
        if (
            $exception !== null
            && trim(
                $exception->getMessage()
            ) !== ''
        ) {
            return trim(
                $exception->getMessage()
            );
        }

        $failureMessage = trim(
            (string) $emailMessage
                ->failure_message
        );

        if ($failureMessage !== '') {
            return $failureMessage;
        }

        return 'Inbound email processing failed.';
    }

    private function appendMetadataEvent(
        mixed $current,
        array $event,
    ): array {
        $metadata = is_array($current)
            ? $current
            : [];

        $events = isset(
            $metadata['events']
        ) && is_array(
            $metadata['events']
        )
            ? $metadata['events']
            : [];

        $events[] = $event;

        $limit = max(
            1,
            (int) config(
                'simpledesk-mail-quarantine.max_metadata_events',
                50
            )
        );

        $metadata['events'] =
            array_slice(
                $events,
                -$limit
            );

        $metadata['last_event'] =
            $event;

        return $metadata;
    }

    private function limitString(
        ?string $value,
        int $length,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr(
            $value,
            0,
            $length
        );
    }
}
