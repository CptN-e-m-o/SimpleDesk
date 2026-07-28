<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\FailedInboundMessageData;
use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\PersistedInboundMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\EmailQuarantineStage;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Quarantine\EmailMessageQuarantineService;
use Illuminate\Support\Facades\DB;
use Throwable;

class InboundNormalizationFailurePersister
{
    public function __construct(
        private readonly MailMessageIdempotencyKeyFactory $keys,
        private readonly RawEmailStorageService $rawStorage,
        private readonly EmailMessageQuarantineService $quarantine,
    ) {
    }

    public function persist(
        MailboxChannel $channel,
        FailedInboundMessageData $failure,
    ): PersistedInboundMessageData {
        $message =
            $failure->acknowledgementMessage;

        $idempotencyKey =
            $this->keys->forIncoming(
                channel: $channel,
                message: $message,
            );

        [$emailMessage, $created, $duplicate] =
            DB::transaction(
                function () use (
                    $channel,
                    $failure,
                    $message,
                    $idempotencyKey,
                ): array {
                    $emailMessage =
                        EmailMessage::query()
                            ->where(
                                'idempotency_key',
                                $idempotencyKey
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        $emailMessage !== null
                        && in_array(
                            $emailMessage->status,
                            [
                                EmailMessageStatus::Received,
                                EmailMessageStatus::Processed,
                            ],
                            true,
                        )
                    ) {
                        return [
                            $emailMessage,
                            false,
                            true,
                        ];
                    }

                    $attributes =
                        $this->messageAttributes(
                            channel: $channel,
                            failure: $failure,
                            idempotencyKey:
                            $idempotencyKey,
                        );

                    if ($emailMessage === null) {
                        $emailMessage =
                            EmailMessage::query()
                                ->create(
                                    $attributes
                                );

                        return [
                            $emailMessage,
                            true,
                            false,
                        ];
                    }

                    $emailMessage->forceFill(
                        $attributes
                    )->save();

                    return [
                        $emailMessage,
                        false,
                        false,
                    ];
                },
                3,
            );

        if ($duplicate) {
            return new PersistedInboundMessageData(
                emailMessage: $emailMessage,
                created: false,
                duplicate: true,
            );
        }

        $rawStorageError = null;

        if ($message->rawMessage !== null) {
            try {
                $this->rawStorage->store(
                    emailMessage: $emailMessage,
                    rawMessage: $message->rawMessage,
                );
            } catch (Throwable $exception) {
                $rawStorageError = [
                    'exception' =>
                        $exception::class,

                    'message' =>
                        $exception->getMessage(),
                ];
            }
        }

        $metadata = $failure->metadata;

        if ($rawStorageError !== null) {
            $metadata['raw_storage_error'] =
                $rawStorageError;
        }

        $this->quarantine->quarantine(
            emailMessageId:
            $emailMessage->id,

            stage:
            EmailQuarantineStage::InboundNormalization,

            exception: null,

            reasonCode:
            $failure->errorCode,

            reasonMessage:
            $failure->errorMessage,

            metadata: array_merge(
                $metadata,
                [
                    'exception_class' =>
                        $failure->exceptionClass,

                    'retryable' =>
                        $failure->retryable,
                ]
            ),
        );

        $emailMessage->forceFill([
            'status' =>
                EmailMessageStatus::Failed,

            'failed_at' => now(),

            'failure_code' =>
                'inbound_normalization_quarantined',

            'failure_message' =>
                mb_substr(
                    $failure->errorMessage,
                    0,
                    10000
                ),
        ])->save();

        return new PersistedInboundMessageData(
            emailMessage:
            $emailMessage->fresh(),

            created: $created,
            duplicate: false,
        );
    }

    private function messageAttributes(
        MailboxChannel $channel,
        FailedInboundMessageData $failure,
        string $idempotencyKey,
    ): array {
        $message =
            $failure->acknowledgementMessage;

        $metadata =
            $message->metadata;

        $metadata['normalization_failure'] = [
            'error_code' =>
                $failure->errorCode,

            'error_message' =>
                $failure->errorMessage,

            'exception_class' =>
                $failure->exceptionClass,

            'retryable' =>
                $failure->retryable,

            'metadata' =>
                $failure->metadata,

            'quarantined_at' =>
                now()->toIso8601String(),
        ];

        return [
            'mailbox_id' =>
                $channel->mailbox_id,

            'mailbox_channel_id' =>
                $channel->id,

            'direction' =>
                EmailMessageDirection::Incoming,

            'driver' =>
                $channel->driver,

            'status' =>
                EmailMessageStatus::Failed,

            'idempotency_key' =>
                $idempotencyKey,

            'external_message_id' =>
                $message->externalMessageId,

            'internet_message_id' =>
                $message->internetMessageId,

            'in_reply_to_message_id' =>
                $message->inReplyToMessageId,

            'reference_message_ids' =>
                $message->references,

            'sender_address' =>
                $message->from->address,

            'sender_name' =>
                $message->from->name,

            'to_recipients' =>
                $this->addressesToArray(
                    $message->to
                ),

            'cc_recipients' =>
                $this->addressesToArray(
                    $message->cc
                ),

            'bcc_recipients' =>
                $this->addressesToArray(
                    $message->bcc
                ),

            'reply_to_recipients' =>
                $this->addressesToArray(
                    $message->replyTo
                ),

            'subject' =>
                $message->subject,

            'text_body' =>
                $message->textBody,

            'html_body' =>
                $message->htmlBody,

            'headers' =>
                $message->headers,

            'metadata' =>
                $metadata,

            'received_at' =>
                $message->receivedAt,

            'processing_started_at' => null,
            'processed_at' => null,

            'failed_at' => now(),

            'failure_code' =>
                'inbound_normalization_quarantined',

            'failure_message' =>
                mb_substr(
                    $failure->errorMessage,
                    0,
                    10000
                ),
        ];
    }

    /**
     * @param array<int, MailAddressData> $addresses
     */
    private function addressesToArray(
        array $addresses
    ): array {
        return array_map(
            static fn (
                MailAddressData $address
            ): array => $address->toArray(),
            $addresses,
        );
    }
}
