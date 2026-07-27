<?php

namespace App\Services\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\NormalizedInboundMessageData;
use App\Data\Admin\Mail\PersistedInboundMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Events\Admin\Mail\InboundEmailStored;
use App\Exceptions\Admin\Mail\InboundMessageAlreadyProcessingException;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\MailboxChannel;
use Illuminate\Support\Facades\DB;
use Throwable;

class IncomingEmailMessagePersister
{
    public function __construct(
        private readonly MailMessageIdempotencyKeyFactory $keys,
        private readonly RawEmailStorageService $rawStorage,
        private readonly MailAttachmentStorageService $attachmentStorage,
        private readonly int $processingLockSeconds,
    ) {
    }

    public function persist(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
    ): PersistedInboundMessageData {
        $idempotencyKey = $this->keys->forIncoming(
            channel: $channel,
            message: $message,
        );

        [$emailMessage, $created, $duplicate] =
            $this->claimMessage(
                channel: $channel,
                message: $message,
                idempotencyKey: $idempotencyKey,
            );

        if ($duplicate) {
            return new PersistedInboundMessageData(
                emailMessage: $emailMessage,
                created: false,
                duplicate: true,
            );
        }

        try {
            if ($message->rawMessage !== null) {
                $this->rawStorage->store(
                    emailMessage: $emailMessage,
                    rawMessage: $message->rawMessage,
                );
            }

            foreach (
                array_values($message->attachments)
                as $position => $attachment
            ) {
                $this->attachmentStorage->store(
                    emailMessage: $emailMessage,
                    attachment: $attachment,
                    position: $position,
                );
            }

            $emailMessage->forceFill([
                'status' => EmailMessageStatus::Received,
                'processing_started_at' => null,
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ])->save();

            InboundEmailStored::dispatch(
                $emailMessage->id
            );

            return new PersistedInboundMessageData(
                emailMessage: $emailMessage->fresh(
                    'attachments'
                ),
                created: $created,
                duplicate: false,
            );
        } catch (Throwable $exception) {
            $emailMessage->forceFill([
                'status' => EmailMessageStatus::Failed,
                'processing_started_at' => null,
                'failed_at' => now(),
                'failure_code' => 'inbound_persistence_failed',
                'failure_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    private function claimMessage(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        string $idempotencyKey,
    ): array {
        return DB::transaction(function () use (
            $channel,
            $message,
            $idempotencyKey,
        ): array {
            $emailMessage = EmailMessage::query()
                ->where(
                    'idempotency_key',
                    $idempotencyKey
                )
                ->lockForUpdate()
                ->first();

            if ($emailMessage !== null) {
                if (in_array(
                    $emailMessage->status,
                    [
                        EmailMessageStatus::Received,
                        EmailMessageStatus::Processed,
                    ],
                    true,
                )) {
                    return [
                        $emailMessage,
                        false,
                        true,
                    ];
                }

                if (
                    $emailMessage->status
                    === EmailMessageStatus::Processing
                    && $emailMessage->processing_started_at !== null
                    && $emailMessage->processing_started_at
                        ->greaterThan(
                            now()->subSeconds(
                                $this->processingLockSeconds
                            )
                        )
                ) {
                    throw new InboundMessageAlreadyProcessingException(
                        $emailMessage->id
                    );
                }

                $emailMessage->forceFill(
                    $this->messageAttributes(
                        channel: $channel,
                        message: $message,
                        idempotencyKey: $idempotencyKey,
                    )
                )->save();

                return [
                    $emailMessage,
                    false,
                    false,
                ];
            }

            $emailMessage = EmailMessage::query()->create(
                $this->messageAttributes(
                    channel: $channel,
                    message: $message,
                    idempotencyKey: $idempotencyKey,
                )
            );

            return [
                $emailMessage,
                true,
                false,
            ];
        });
    }

    private function messageAttributes(
        MailboxChannel $channel,
        NormalizedInboundMessageData $message,
        string $idempotencyKey,
    ): array {
        return [
            'mailbox_id' => $channel->mailbox_id,
            'mailbox_channel_id' => $channel->id,
            'direction' => EmailMessageDirection::Incoming,
            'driver' => $channel->driver,
            'status' => EmailMessageStatus::Processing,
            'idempotency_key' => $idempotencyKey,
            'external_message_id' =>
                $message->externalMessageId,
            'internet_message_id' =>
                $message->internetMessageId,
            'in_reply_to_message_id' =>
                $message->inReplyToMessageId,
            'reference_message_ids' =>
                $message->references,
            'sender_address' => $message->from->address,
            'sender_name' => $message->from->name,
            'to_recipients' =>
                $this->addressesToArray($message->to),
            'cc_recipients' =>
                $this->addressesToArray($message->cc),
            'bcc_recipients' =>
                $this->addressesToArray($message->bcc),
            'reply_to_recipients' =>
                $this->addressesToArray($message->replyTo),
            'subject' => $message->subject,
            'text_body' => $message->textBody,
            'html_body' => $message->htmlBody,
            'headers' => $message->headers,
            'metadata' => $message->metadata,
            'received_at' => $message->receivedAt,
            'processing_started_at' => now(),
            'processed_at' => null,
            'failed_at' => null,
            'failure_code' => null,
            'failure_message' => null,
        ];
    }

    /**
     * @param array<int, MailAddressData> $addresses
     */
    private function addressesToArray(
        array $addresses
    ): array {
        return array_map(
            static fn (MailAddressData $address): array =>
            $address->toArray(),
            $addresses,
        );
    }
}
